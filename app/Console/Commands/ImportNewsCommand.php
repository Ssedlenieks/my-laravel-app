<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Post;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ImportNewsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'news:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import news from RSS feed';

    /**
     * Execute the console command.
     */
    public function handle() {
        $rssFeedUrl = 'https://www.lsm.lv/rss/?lang=lv&catid=14';
        $rssContent = file_get_contents($rssFeedUrl);

        if ($rssContent === false) {
            $this->error('Failed to retrieve rss feed.');
            return 1;
        }

        $rss = simplexml_load_string($rssContent, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($rss === false) {
            $this->error('Failed to parse RSS feed.');
            return 1;
        }

        foreach ($rss->channel->item as $item) {
            $title = (string) $item->title;
            $link = (string) $item->link;
            $description = (string) $item->description;
            $pubDate = (string) $item->pubDate;

            if (Post::where('title', $title)->exists()) {
                continue; 
            }

           
            $post = new Post();
            $post->title = $title;
            $post->content = $description;
           
            $post->created_at = date('Y-m-d H:i:s', strtotime($pubDate));
            if (\App\Models\User::where('id', 1)->exists()) {
                $post->user_id = 1;
            } else {
                $firstUser = \App\Models\User::first();
                $post->user_id = $firstUser ? $firstUser->id : null;
            }
            $post->save();

            $imageUrl = null;
            if (isset($item->enclosure) && isset($item->enclosure['url'])) {
                $imageUrl = (string) $item->enclosure['url'];
            }
            if (!$imageUrl) {
                $media = $item->children('http://search.yahoo.com/mrss/');
                if ($media && isset($media->content) && isset($media->content->attributes()->url)) {
                    $imageUrl = (string) $media->content->attributes()->url;
                }
            }
            if (!$imageUrl) {
                try {
                    $resp = Http::get($link);
                    if ($resp->ok()) {
                        if (preg_match('/<meta property="og:image" content="([^"]+)"/i', $resp->body(), $m)) {
                            $imageUrl = $m[1];
                        }
                    }
                } catch (\Exception $e) {
                }
            }

            if ($imageUrl) {
                try {
                    $resp = Http::get($imageUrl);
                    if ($resp->ok()) {
                        $contentType = $resp->header('Content-Type') ?? 'image/jpeg';
                        $ext = explode('/', $contentType)[1] ?? 'jpg';
                        $filename = 'posts/' . Str::random(24) . '.' . $ext;
                        Storage::disk('public')->put($filename, $resp->body());
                        $post->image = $filename;
                        $post->save();
                    }
                } catch (\Exception $e) {
                }
            }
            $category = Category::firstOrCreate(['name' => 'News']);
            $post->categories()->syncWithoutDetaching([$category->id]);

            $post->assignCategoriesByKeywords();
        }
    }
}


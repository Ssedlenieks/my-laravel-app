<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LingvanexController extends Controller
{
    public function lookupWord(Request $request)
    {
        $word = $request->query('word');
        if (!$word) {
            return response()->json(['error' => 'Nav nordts vrds'], 400);
        }

        // Clean the word from HTML entities, quotes, and extra characters
        $word = html_entity_decode($word, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $word = trim($word, " \t\n\r\0\x0B\"'");
        $word = str_replace(['"', '"', '"', "'", "'"], '', $word);
        $word = preg_replace('/[^\p{L}\p{N}\s\-]/u', '', $word);
        $word = trim($word);

        if (empty($word)) {
            return response()->json(['error' => 'Nav nordts dergs vrds'], 400);
        }

        try {
            // Primary: Try Lingvanex translation approach (since you have a paid API key)
            $lingvanexResult = $this->tryLingvanexTranslationApproach($word);

            // If Lingvanex found a definition, return it
            if ($lingvanexResult->getData()->definition !== "Vārda definīcija nav atrasta. Iespējams, tas ir īpašvārds, tehnisks termins vai nepareizi uzrakstīts vārds.") {
                return $lingvanexResult;
            }

            // Fallback: Check our built-in dictionary
            $result = $this->trySimpleLatvianDictionary($word);
            return $result;

        } catch (\Exception $e) {
            \Log::error('Dictionary Lookup Exception', ['message' => $e->getMessage()]);
            return $this->trySimpleLatvianDictionary($word);
        }
    }

    private function trySimpleLatvianDictionary($word)
    {
        // Extended Latvian word dictionary with common words
        $commonWords = [
            'mja' => '(lietvrds) Dzvojam ka',
            'saule' => '(lietvrds) Zvaigzne, kas apgaismo Zemi',
            'dens' => '(lietvrds) Dzidrs idrums',
            'grmata' => '(lietvrds) Drukts vai rakstts darbs',
            'skola' => '(lietvrds) Izgltbas iestde',
            'darbs' => '(lietvrds) Darbba, nodarboans',
            'laiks' => '(lietvrds) Ilgums, periods',
            'cilvks' => '(lietvrds) Saprtga dzva btne',
            'valsts' => '(lietvrds) Politiska vienba',
            'pasaule' => '(lietvrds) Visa eksistjo realitte',
            'mlestba' => '(lietvrds) Dzia pieerans un muma sajta',
            'draugs' => '(lietvrds) Tuva, uzticama persona',
            'imene' => '(lietvrds) Radinieku grupa',
            'prieks' => '(lietvrds) Pozitva emocija, laimes sajta',
            'bdas' => '(lietvrds) Skumjas, spes',
            'bailes' => '(lietvrds) Baiu, izbu sajta',
            'zias' => '(lietvrds) Informcija par notikumiem',
            'valoda' => '(lietvrds) Sazias ldzeklis',
            'vrds' => '(lietvrds) Runas vai rakstu vienba',
            'ststs' => '(lietvrds) Izststts notikums',
            'jautjums' => '(lietvrds) Vaicjums, problma',
            'atbilde' => '(lietvrds) Reakcija uz jautjumu',
            'dienas' => '(lietvrds) 24 stundu periods',
            'nakts' => '(lietvrds) Tumais diennakts laiks',
            'rts' => '(lietvrds) Dienas skums',
            'vakars' => '(lietvrds) Dienas beigas',
            'datums' => '(lietvrds) Konkrts laika moments',
            'nkotne' => '(lietvrds) Nkamais laiks',
            'pagtne' => '(lietvrds) Iepriekjais laiks',
            'tagadne' => '(lietvrds) Pareizjais laiks',
            'pilsta' => '(lietvrds) Lielks apdzvots punkts',
            'ciems' => '(lietvrds) Mazs apdzvots punkts',
            'drzs' => '(lietvrds) Audzanas vieta',
            'parks' => '(lietvrds) Atptas zona ar kokiem',
            'veikals' => '(lietvrds) Tirdzniecbas vieta',
            'kafejnca' => '(lietvrds) stuves vieta',
            'zinanas' => '(lietvrds) Iegta informcija',
            'izgltba' => '(lietvrds) Mcbu process',
            'kultra' => '(lietvrds) Sabiedrbas radts vrtbas',
            'mksla' => '(lietvrds) Rado darbba',
            'zintne' => '(lietvrds) Sistemtiska izpte',
            'skolotjs' => '(lietvrds) Persona, kas mca',
            'students' => '(lietvrds) Persona, kas mcs',
            'aprbs' => '(lietvrds) Drbes, ko uzvelk uz ermea',
            'krekls' => '(lietvrds) Augdaas aprbs',
            'bikses' => '(lietvrds) Kju aprbs',
            'kurpes' => '(lietvrds) Kju aizsargs',
            'cimdi' => '(lietvrds) Roku aizsargs',
            'cepure' => '(lietvrds) Galvas segs',
            'diens' => '(lietvrds) Uztura vielas',
            'maize' => '(lietvrds) Pamata uztura produkts',
            'piens' => '(lietvrds) Balts dzriens no govs',
            'gaa' => '(lietvrds) Dzvnieku muskui k prtika',
            'drzei' => '(lietvrds) Augiem audzts prtikas vielas',
            'augi' => '(lietvrds) Saldu produkti no kokiem',
            'dators' => '(lietvrds) Elektroniska skaitoanas ierce',
            'telefons' => '(lietvrds) Sazias ierce',
            'internets' => '(lietvrds) Globls datortkls',
            'televizors' => '(lietvrds) Attlu un skaas ierce',
            'radio' => '(lietvrds) Skaas prraides ierce',
            'koks' => '(lietvrds) Augsts augs ar stumbru',
            'zle' => '(lietvrds) Zas zemes segs',
            'zieds' => '(lietvrds) Krsaina auga daa',
            'dzvnieks' => '(lietvrds) Dzva btne',
            'putns' => '(lietvrds) Lidojos dzvnieks',
            'zivs' => '(lietvrds) den dzvojos dzvnieks',
            'darana' => '(lietvrds) Darbbas veikana',
            'mcans' => '(lietvrds) Zinanu apguve',
            'lasana' => '(lietvrds) Teksta uztvere',
            'rakstana' => '(lietvrds) Teksta radana',
            'skrjiena' => '(lietvrds) tra kustba',
            'staigana' => '(lietvrds) Lna kustba',
            'krsa' => '(lietvrds) Vizul paba',
            'sarkans' => '(pabas vrds) Uguns krsa',
            'zils' => '(pabas vrds) Debess krsa',
            'za' => '(pabas vrds) Zles krsa',
            'dzeltens' => '(pabas vrds) Saules krsa',
            'balts' => '(pabas vrds) Sniega krsa',
            'melns' => '(pabas vrds) Nakts krsa',
            'galva' => '(lietvrds) Augj ermea daa',
            'acs' => '(lietvrds) Redzes orgns',
            'mute' => '(lietvrds) anas un runanas orgns',
            'roka' => '(lietvrds) Augjs ekstremittes',
            'kja' => '(lietvrds) Apakjs ekstremittes',
            'sirds' => '(lietvrds) Asinsrites orgns',
            'lietus' => '(lietvrds) dens nokrites no debesm',
            'sniegs' => '(lietvrds) Baltas prslas no debesm',
            'vj' => '(lietvrds) Gaisa kustba',
            'mkoi' => '(lietvrds) dens tvaiku krjumi debess'
        ];

        $word = strtolower(trim($word));

        if (isset($commonWords[$word])) {
            return response()->json([
                'word' => $word,
                'definition' => $commonWords[$word],
                'partOfSpeech' => 'pamata'
            ]);
        }

        return response()->json([
            'word' => $word,
            'definition' => "Vārda definīcija nav atrasta. Iespējams, tas ir īpašvārds, tehnisks termins vai nepareizi uzrakstīts vārds.",
            'partOfSpeech' => 'nezināms'
        ]);
    }

    private function tryLingvanexTranslationApproach($word)
    {
        try {
            // Get your API key from environment variable
            $apiKey = env('LINGVANEX_API_KEY');

            if (!$apiKey) {
                \Log::warning('Lingvanex API key not found in environment');
                return response()->json([
                    'word' => $word,
                    'definition' => "Vārda definīcija nav atrasta. Iespējams, tas ir īpašvārds, tehnisks termins vai nepareizi uzrakstīts vārds.",
                    'partOfSpeech' => 'nezināms'
                ]);
            }

            // Step 1: Translate Latvian word to English using Lingvanex
            $translationResponse = Http::timeout(10)->withoutVerifying()->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json'
            ])->post('https://api-b2b.backenster.com/b1/api/v3/translate', [
                'from' => 'lv',
                'to' => 'en',
                'data' => $word,
                'platform' => 'api'
            ]);

            if ($translationResponse->successful()) {
                $translationData = $translationResponse->json();
                $englishWord = $translationData['result'] ?? '';

                \Log::info('Lingvanex Translation', ['latvian' => $word, 'english' => $englishWord]);

                if ($englishWord && $englishWord !== $word && strlen($englishWord) > 1) {
                    // Step 2: Get English definition using Free Dictionary API
                    $definitionResponse = Http::timeout(10)->withoutVerifying()->get("https://api.dictionaryapi.dev/api/v2/entries/en/" . urlencode(strtolower($englishWord)));

                    if ($definitionResponse->successful()) {
                        $definitionData = $definitionResponse->json();

                        if (!empty($definitionData) && isset($definitionData[0]['meanings'])) {
                            $meaning = $definitionData[0]['meanings'][0];
                            $partOfSpeech = $meaning['partOfSpeech'] ?? '';
                            $definition = $meaning['definitions'][0]['definition'] ?? '';

                            \Log::info('English Definition Found', ['word' => $englishWord, 'definition' => $definition]);

                            if ($definition) {
                                // Step 3: Translate the English definition back to Latvian
                                $latvianDefResponse = Http::timeout(10)->withoutVerifying()->withHeaders([
                                    'Authorization' => 'Bearer ' . $apiKey,
                                    'Content-Type' => 'application/json'
                                ])->post('https://api-b2b.backenster.com/b1/api/v3/translate', [
                                    'from' => 'en',
                                    'to' => 'lv',
                                    'data' => $definition,
                                    'platform' => 'api'
                                ]);

                                if ($latvianDefResponse->successful()) {
                                    $latvianDefData = $latvianDefResponse->json();
                                    $latvianDefinition = $latvianDefData['result'] ?? $definition;

                                    \Log::info('Latvian Definition Translated', ['english' => $definition, 'latvian' => $latvianDefinition]);

                                    // Translate part of speech to Latvian
                                    $latvianPartOfSpeech = $this->translatePartOfSpeech($partOfSpeech);

                                    return response()->json([
                                        'word' => $word,
                                        'definition' => "({$latvianPartOfSpeech}) {$latvianDefinition}",
                                        'partOfSpeech' => $latvianPartOfSpeech,
                                        'englishTranslation' => $englishWord,
                                        'source' => 'lingvanex'
                                    ]);
                                } else {
                                    \Log::warning('Failed to translate definition to Latvian', ['response' => $latvianDefResponse->body()]);

                                    // Fallback: return English definition if Latvian translation fails
                                    $latvianPartOfSpeech = $this->translatePartOfSpeech($partOfSpeech);
                                    return response()->json([
                                        'word' => $word,
                                        'definition' => "({$latvianPartOfSpeech}) {$definition}",
                                        'partOfSpeech' => $latvianPartOfSpeech,
                                        'englishTranslation' => $englishWord,
                                        'source' => 'lingvanex-english'
                                    ]);
                                }
                            }
                        }
                    } else {
                        \Log::warning('English dictionary lookup failed', ['word' => $englishWord, 'response' => $definitionResponse->body()]);
                    }
                }
            } else {
                \Log::warning('Lingvanex translation failed', ['word' => $word, 'response' => $translationResponse->body()]);
            }

            // If Lingvanex approach fails, return "not found"
            return response()->json([
                'word' => $word,
                'definition' => "Vārda definīcija nav atrasta. Iespējams, tas ir īpašvārds, tehnisks termins vai nepareizi uzrakstīts vārds.",
                'partOfSpeech' => 'nezināms'
            ]);

        } catch (\Exception $e) {
            \Log::error('Lingvanex Translation Approach Exception', ['message' => $e->getMessage(), 'word' => $word]);

            return response()->json([
                'word' => $word,
                'definition' => "Vārda definīcija nav atrasta. Iespējams, tas ir īpašvārds, tehnisks termins vai nepareizi uzrakstīts vārds.",
                'partOfSpeech' => 'nezināms'
            ]);
        }
    }

    private function translatePartOfSpeech($englishPartOfSpeech)
    {
        $translations = [
            'noun' => 'lietvārds',
            'verb' => 'darbības vārds',
            'adjective' => 'īpašības vārds',
            'adverb' => 'apstākļa vārds',
            'pronoun' => 'vietniekvārds',
            'preposition' => 'prievārds',
            'conjunction' => 'saiklis',
            'interjection' => 'izsauksmes vārds',
            'article' => 'artikuls',
            'numeral' => 'skaitļa vārds',
            'participle' => 'divdabis'
        ];

        return $translations[strtolower($englishPartOfSpeech)] ?? $englishPartOfSpeech;
    }

    public function testApiDirect(Request $request)
    {
        $apiKey = env('LINGVANEX_API_KEY');
        $word = $request->query('word', 'māja');

        try {
            $response = Http::timeout(10)->withoutVerifying()->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json'
            ])->post('https://api-b2b.backenster.com/b1/api/v3/translate', [
                'from' => 'lv',
                'to' => 'en',
                'data' => $word,
                'platform' => 'api'
            ]);

            return response()->json([
                'success' => $response->successful(),
                'status' => $response->status(),
                'word' => $word,
                'response' => $response->json(),
                'body' => $response->body()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'word' => $word
            ]);
        }
    }
}

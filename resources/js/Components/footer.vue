<template>
    <footer class="app-footer">
        <div class="footer-container">
            <div class="footer-section">
                <h3>Par Labaziņa</h3>
                <p>
                    Labaziņa ir uzticams ziņu avots, kas sniedz jaunākās ziņas, ieskatus un atjauninājumus
                    visā pasaulē. Sekojiet līdzi un sazinieties ar mūsu veltīto žurnālistikas kopienu.
                </p>
            </div>

            <div class="footer-section">
                <h3>Sazinieties ar mums</h3>
                <p>Epasts : <a href="mailto:info@labazina.com">info@labazina.com</a></p>
                <p>Tālrunis: +371 123 4567</p>
                <div class="social-icons">
                    <a href="#" aria-label="Instagram">Instagram</a>
                    <a href="#" aria-label="Facebook">Facebook</a>
                    <a href="#" aria-label="TikTok">TikTok</a>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p>© 2025 Labazina — All rights reserved.</p>
        </div>
    </footer>
</template>

<script>
export default {
    name: "AppFooter",
};
</script>

<style scoped>
.app-footer {
    background-color: var(--post-bg);
    font-family: "AbrilFatface", sans-serif;
    color: var(--post-text);
    padding: 40px 20px 20px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
    transition: background-color 0.3s ease, color 0.3s ease, box-shadow 0.3s ease;
}

body.dark .app-footer {
    background-color: #222;
    color: var(--post-text);
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.5);
}

.footer-container {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 40px;
    max-width: 1200px;
    margin: 0 auto;
}

.footer-section {
    flex: 1 1 240px;
}

.footer-section h3 {
    font-size: 20px;
    margin-bottom: 12px;
    color: var(--post-text);
}

.footer-section p {
    font-size: 14px;
    line-height: 1.5;
    margin: 6px 0;
    color: var(--post-text);
}

.footer-section a {
    color: var(--post-text);
    text-decoration: none;
    transition: color 0.3s ease;
}

.footer-section a:hover {
    color: #f87171;
}

body.dark .footer-section a {
    color: var(--post-text);
}

body.dark .footer-section a:hover {
    color: #f87171;
}

.social-icons {
    display: flex;
    gap: 16px;
    margin-top: 8px;
}

.social-icons a {
    font-size: 14px;
    color: var(--post-text);
}

.footer-bottom {
    text-align: center;
    margin-top: 30px;
    font-size: 13px;
    opacity: 0.8;
    color: var(--post-text);
}

@media (max-width: 768px) {
    .footer-container {
        flex-direction: column;
        align-items: center;
        gap: 24px;
    }
    .footer-section {
        text-align: center;
    }
}
</style>

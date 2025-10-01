<template>
    <footer class="app-footer">
        <div class="footer-container">
            <div class="footer-section">
                <h3>About Mireqx</h3>
                <p>
                    Mireqx is a community-driven fashion platform where enthusiasts and professionals share
                    insights, trends, and stories. Join us to explore the world of style and creativity.
                </p>
            </div>

            <div class="footer-section">
                <h3>Contact Us</h3>
                <p>Email: <a>info@mireqx.com</a></p>
                <p>Phone: +371 123 4567</p>
                <div class="social-icons">
                    <a>Instagram</a>
                    <a>Facebook</a>
                    <a>TikTok</a>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p>© 2025 Mireqx — All rights reserved.</p>
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
    background-color: whitesmoke;
    font-family: "Akira Expanded", sans-serif;
    color: black;
    padding: 40px 20px 20px;
    box-shadow: rgb(244, 153, 153) 1px 1px 10px;
    transition: background-color 0.3s ease, color 0.3s ease, box-shadow 0.3s ease;
}

body.dark .app-footer {
    background-color: #222;
    color: whitesmoke;
    box-shadow: rgb(0, 0, 0) 1px 1px 10px;
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
}

.footer-section p {
    font-size: 14px;
    line-height: 1.5;
    margin: 6px 0;
}

.footer-section a {
    color: black;
    text-decoration: none;
    transition: color 0.3s ease;
}

.footer-section a:hover {
    color: rgb(255, 0, 0);
}

body.dark .footer-section a {
    color: whitesmoke;
}

body.dark .footer-section a:hover {
    color: rgb(255, 0, 0);
}

.social-icons {
    display: flex;
    gap: 16px;
    margin-top: 8px;
}

.social-icons a {
    font-size: 14px;
}

.footer-bottom {
    text-align: center;
    margin-top: 30px;
    font-size: 13px;
    opacity: 0.8;
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

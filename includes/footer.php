<?php
?>
    </div> <!-- End content-wrapper -->
    
    <footer>
        <div class="footer-column">
            <a href="/nakai/index.php" class="logo-link">
                <h2 class="footer-logo">
                    Nakai Nakai
                </h2>
            </a>
        </div>
        <div class="footer-column">
            <div class="social-icons">
                <a href="#"><i class="fab fa-facebook"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-linkedin"></i></a>
            </div>
        </div>
        <div class="footer-column">
            <p>
                Developed by Ruvarashe Sadya
            </p>
        </div>
        
        <style>
            footer {
                background-color: #f8f9fa;
                padding: 2rem;
                margin-top: auto;
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 1rem;
            }
            
            .footer-logo {
                font-family: 'Faculty Glyphic', sans-serif;
                margin: 0;
                font-size: 1.5rem;
            }

            .logo-link {
                text-decoration: none;
                color: black;
            }
            
            .footer-column {
                flex: 1;
                min-width: 200px;
                text-align: center;
            }
            
            .social-icons {
                display: flex;
                gap: 1rem;
                justify-content: center;
            }
            
            .social-icons a {
                color: #333;
                font-size: 1.5rem;
                transition: color 0.3s ease;
            }
            
            .social-icons a:hover {
                color: #007bff;
            }
            
            /* Ensure footer stays at bottom */
            body {
                min-height: 100vh;
                display: flex;
                flex-direction: column;
            }
            
            .content-wrapper {
                flex: 1;
            }
        </style>
    </footer>
</body>
</html>

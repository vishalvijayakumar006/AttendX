<?php
// Folder: C:\Users\Vaishnavi.V.A\.gemini\antigravity\scratch\smart-event-entry\includes
// File: footer.php
// Purpose: Main page footer template to close tags opened in header.php.
?>
    </main>

    <style>
        footer {
            border-top: 1px solid var(--border-color);
            background-color: rgba(11, 19, 41, 0.5);
            padding: 24px;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-top: auto;
        }

        .footer-content {
            max-width: var(--max-width);
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .footer-copyright {
            font-weight: 400;
        }

        .footer-brand {
            font-weight: 600;
            color: var(--text-main);
        }

        @media (max-width: 600px) {
            .footer-content {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>

    <footer>
        <div class="footer-content">
            <div class="footer-copyright">
                &copy; <?php echo date('Y'); ?> <span class="footer-brand">SmartEntry</span>. All rights reserved.
            </div>
            <div>
                Built for Portfolios &amp; Placements.
            </div>
        </div>
    </footer>
</body>
</html>

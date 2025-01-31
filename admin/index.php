<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Scanner</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .scan-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #2196F3;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        .scan-button:hover {
            background: #1976D2;
        }

        .scanner-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .scanner-content {
            background: white;
            padding: 24px;
            border-radius: 12px;
            width: 320px;
            position: relative;
        }

        .close-button {
            position: absolute;
            right: 16px;
            top: 16px;
            width: 32px;
            height: 32px;
            background: #f0f0f0;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #333;
            transition: background-color 0.3s;
        }

        .close-button:hover {
            background: #e0e0e0;
        }

        #reader {
            width: 100%;
            margin-top: 20px;
        }

        .scan-result {
            margin-top: 16px;
            padding: 12px;
            background: #f5f5f5;
            border-radius: 8px;
            display: none;
        }

        .scan-result p {
            margin: 4px 0;
            word-break: break-all;
        }

        /* Hide html5-qrcode's default elements we don't want to show */
        #reader__dashboard_section_swaplink {
            display: none !important;
        }

        #reader__dashboard_section_csr button {
            padding: 8px 16px;
            background: #2196F3;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }

        /* Responsive styles */
        @media (max-width: 480px) {
            .scanner-content {
                width: 90%;
                padding: 16px;
            }
        }
    </style>
</head>
<body>
    <!-- Scan Button -->
    <button class="scan-button" id="openScanner">
        <i class="fas fa-qrcode"></i>
        <span>Scan QR Code</span>
    </button>

    <!-- Scanner Modal -->
    <div class="scanner-modal" id="scannerModal">
        <div class="scanner-content">
            <button class="close-button" id="closeScanner">&times;</button>
            <div id="reader"></div>
            <div class="scan-result" id="scanResult">
                <p class="result-label">Scanned Result:</p>
                <p class="result-text" id="resultText"></p>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
        let html5QrcodeScanner = null;

        // Button elements
        const openButton = document.getElementById('openScanner');
        const closeButton = document.getElementById('closeScanner');
        const modal = document.getElementById('scannerModal');
        const resultDiv = document.getElementById('scanResult');
        const resultText = document.getElementById('resultText');

        // Initialize scanner
        function initializeScanner() {
            html5QrcodeScanner = new Html5QrcodeScanner(
                "reader",
                {
                    fps: 10,
                    qrbox: { width: 250, height: 250 },
                    rememberLastUsedCamera: true,
                    showTorchButtonIfSupported: true
                },
                false
            );

            html5QrcodeScanner.render(onScanSuccess, onScanError);
        }

        // Success callback
        function onScanSuccess(decodedText, decodedResult) {
            console.log('QR Code scanned:', decodedText);
            resultDiv.style.display = 'block';
            resultText.textContent = decodedText;

            // Handle URL scanning
            if (decodedText.startsWith('http://') || decodedText.startsWith('https://')) {
                window.location.href = decodedText;
            }
        }

        // Error callback
        function onScanError(error) {
            console.warn(`QR Code scanning failed: ${error}`);
        }

        // Close scanner
        function closeScanner() {
            modal.style.display = 'none';
            resultDiv.style.display = 'none';
            
            if (html5QrcodeScanner) {
                html5QrcodeScanner.clear().catch(error => {
                    console.error('Failed to clear scanner:', error);
                });
                html5QrcodeScanner = null;
            }
        }

        // Event Listeners
        openButton.addEventListener('click', () => {
            modal.style.display = 'flex';
            initializeScanner();
        });

        closeButton.addEventListener('click', closeScanner);

        // Close modal when clicking outside
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeScanner();
            }
        });

        // Handle escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.style.display === 'flex') {
                closeScanner();
            }
        });
    </script>
</body>
</html>
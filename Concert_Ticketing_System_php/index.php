<?php

require_once 'app.php'; 

$concerts = getConcerts();

?>

<!DOCTYPE html>
<html>
<head>
    <title>Concert Ticketing System</title>
</head>
<body>
    <h1>Available Concerts</h1>
    <div>
    <?php foreach ($concerts as $concert): ?>
        <div style="margin-bottom:20px; padding:10px; border:1px solid #ccc;">
            <h2><?php echo htmlspecialchars($concert['title']); ?></h2>
            <img src="<?php echo htmlspecialchars($concert['imageUrl']); ?>" alt="<?php echo htmlspecialchars($concert['title']); ?>" style="max-width:100%; height:auto;">
            <p>Price per Ticket: <strong>KSh <?php echo number_format($concert['price']); ?></strong></p>
            <button onclick="openForm(<?php echo $concert['concertId']; ?>, '<?php echo htmlspecialchars($concert['title']); ?>', <?php echo $concert['price']; ?>)">
                Pay
            </button>            
        </div>
    <?php endforeach; ?>
</div>

    <!-- Booking Form Model -->
    <div id="booking-form" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background-color:rgba(0,0,0,0.5); align-items:center; justify-content:center;">
    <div style="background-color:white; margin:50px auto; padding:20px; width:300px; position:relative;">
    
        <div>
        <h3 id="concert-title">Concert Title</h3>
        <button id="close" style="position:absolute; top:10px; right:10px;">X</button>
        </div>

        <div>
        <p>Price per Ticket:</p>
        <p id="price">KSh 0</p>
        </div>

        <form id="booking-form-element" method="post">
        <!-- Capture the concert id -->
        <input type="hidden" id="concert-id" name="concertId">

        <div>
            <label>Your Name</label>
            <input type="text" id="customer-name" name="customerName" required>
        </div>

        <div>
            <label>Phone Number</label>
            <input type="tel" id="phone-number" name="phoneNumber" placeholder="e.g. 0712345678" required>          
        </div>

        <div>
            <label>Number of Tickets</label>
            <div>
            <button type="button" id="decrease-quantity">-</button>
            <input type="number" id="quantity" name="quantity" value="1" min="1" readonly>
            <button type="button" id="increase-quantity">+</button>
            </div>
        </div>

        <div>
            <p>Total Amount:</p>
            <p id="total-amount">KSh 0</p>
        </div>

        <button type="submit" id="submit-booking">
            Book Ticket
        </button>

        <!-- Loading button -->
        <button type="button" id="loading-button" class="hidden" disabled>
            Processing Payment...
        </button>
        </form>
    </div>
    </div>


    <script>
        const bookingForm = document.getElementById('booking-form');
        const closingForm = document.getElementById('close');
        const phoneInput = document.getElementById('phone-number');
        const concertTitle = document.getElementById('concert-title');
        const concertPrice = document.getElementById('price');
        const concertIdInput = document.getElementById('concert-id');
        const quantityInput = document.getElementById('quantity');
        const totalAmount = document.getElementById('total-amount');
        const submitBookingBtn = document.getElementById('submit-booking');
        const loadingButton = document.getElementById('loading-button');

        let currentPrice = 0;

        // Function to open form with data
        function openForm(id, title, price) {
        bookingForm.style.display = 'flex';
        concertTitle.innerText = title;
        concertPrice.innerText = 'KSh ' + price;
        concertIdInput.value = id;
        currentPrice = price;
        quantityInput.value = 1;
        updateTotal();
        }

        ////Closing form. bookingForm calls the id of the form and display the form to None which
        //disappears
        closingForm.addEventListener('click', function() {
            bookingForm.style.display = 'none';
        });

        // Quantity buttons
        document.getElementById('decrease-quantity').addEventListener('click', () => {
        let qty = parseInt(quantityInput.value);
        if (qty > 1) {
            quantityInput.value = qty - 1;
            updateTotal();
        }
        });

        document.getElementById('increase-quantity').addEventListener('click', () => {
        let qty = parseInt(quantityInput.value);
        quantityInput.value = qty + 1;
        updateTotal();
        });

        function updateTotal() {
        const qty = parseInt(quantityInput.value);
        totalAmount.innerText = 'KSh ' + (currentPrice * qty);
        }

        // Phone number formatting and validation
        function formatPhoneNumber(phone) {
        phone = phone.replace(/\D/g, '');

        if (phone.startsWith('254')) {
            return phone;
        } else if (phone.startsWith('0')) {
            return '254' + phone.slice(1);
        } else if (phone.startsWith('+254')) {
            return phone.slice(1);
        }

        return phone;
        }

        function validatePhone(phone) {
        const regex = /^254[17][0-9]{8}$/;
        return regex.test(phone);
        }

        phoneInput.addEventListener('input', function(e) {
        let formattedNumber = formatPhoneNumber(e.target.value);
        e.target.value = formattedNumber;
        });

        // Handle form submission
        bookingForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        console.log("check point 1");

        // Get phone number
        const rawPhoneInput = phoneInput.value.trim();
        const phoneNumberFormatted = formatPhoneNumber(rawPhoneInput);

        // Validate phone number
        if (!validatePhone(phoneNumberFormatted)) {
            createToast('Please enter a valid phone number (e.g., 0712345678)', 'error');
            return;
        }
        console.log("wow we are here")

        // Prepare payload
        const payload = {
            concertId: parseInt(concertIdInput.value),
            customerName: document.getElementById('customer-name').value.trim(),
            phoneNumber: phoneNumberFormatted,
            quantity: parseInt(quantityInput.value)
        };
        console.log(payload);

        // Show loading state
        submitBookingBtn.classList.add('hidden');
        loadingButton.classList.remove('hidden');

        try {
            const response = await fetch('/api/make-payment', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload)
            });
            console.log(response);

            const data = await response.json();

            if (response.ok) {
            createToast('Payment initiated successfully. Please check your phone to complete the transaction.', 'success');

            // You can implement checkPaymentStatus here if you want (optional)
            } else {
            createToast(data.error || 'Failed to process payment. Please try again.', 'error');
            submitBookingBtn.classList.remove('hidden');
            loadingButton.classList.add('hidden');
            }
        } catch (error) {
            console.error('Error:', error);
            createToast('An error occurred. Please try again later.', 'error');
            submitBookingBtn.classList.remove('hidden');
            loadingButton.classList.add('hidden');
        }
        });

        
        function createToast(message, type = 'error') {
        alert(message); 
        }

        // Initialize
        //fetchMovies();
    </script>
</body>
</html>

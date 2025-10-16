<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Borrow Confirmation • TapNBorrow</title>
  <style>
    body {
      font-family: system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
      background: #f3f4f6;
      color: #111827;
      margin: 0;
      padding: 0;
    }
    .container {
      background: #ffffff;
      max-width: 640px;
      margin: 40px auto;
      padding: 30px 40px;
      border-radius: 16px;
      box-shadow: 0 8px 25px rgba(0,0,0,.08);
    }
    .header {
      text-align: center;
      border-bottom: 3px solid #2563eb;
      padding-bottom: 14px;
      margin-bottom: 24px;
    }
    .header h1 {
      color: #2563eb;
      font-size: 26px;
      font-weight: 800;
      margin: 0;
    }
    p {
      font-size: 15px;
      line-height: 1.6;
      color: #374151;
    }
    .highlight {
      background: #eff6ff;
      padding: 10px 14px;
      border-left: 4px solid #2563eb;
      border-radius: 8px;
      margin: 16px 0;
    }
    .items {
      background: #f9fafb;
      padding: 12px 14px;
      border-radius: 8px;
      margin: 12px 0;
    }
    ul {
      padding-left: 18px;
    }
    .footer {
      text-align: center;
      margin-top: 24px;
      font-size: 13px;
      color: #6b7280;
      border-top: 1px solid #e5e7eb;
      padding-top: 10px;
    }
    .footer a {
      color: #2563eb;
      text-decoration: none;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>Borrow Confirmation</h1>
    </div>

    <p>Hello <strong>{{ $name }}</strong>,</p>

    <p>Thank you for using <b>TapNBorrow</b>! Your borrowing has been recorded successfully. Below are your borrow details:</p>

    <div class="highlight">
      <p><b>Borrow Date:</b> {{ $borrow_date ?? '-' }}<br>
         <b>Due Date:</b> {{ $due_date ?? '-' }}</p>
    </div>

    <div class="items">
      <p><b>Borrowed Item(s):</b></p>
      <ul>
        @foreach($items as $item)
          <li>{{ $item['item_id'] ?? '' }} — {{ $item['name'] ?? '' }}</li>
        @endforeach
      </ul>
    </div>

    <p>Please ensure to return your items by the due date to avoid any delays or penalties.</p>

    <p>If you have any questions, contact our support team at
      <a href="mailto:support@tapnborrow.com">support@tapnborrow.com</a>.
    </p>

    <div class="footer">
      <p>© {{ date('Y') }} TapNBorrow • Faster, Smarter with NFC</p>
    </div>
  </div>
</body>
</html>

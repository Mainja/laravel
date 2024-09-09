<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>You Have Mail From The Website</title>
</head>
<body>
    <h2>You have received an email from {{ $name }} via the website</h2>
    <p>Name: {{ $name }}</p>
    <p>Email: {{ $sender_email }}</p>
    <p>Phone number: {{ $phone_number }}</p>
    <p>
        {{ $sent_message }}
    </p>
</body>
</html>
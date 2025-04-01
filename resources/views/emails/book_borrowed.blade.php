<!DOCTYPE html>
<html>

<head>
    <title>Book Borrowed Successfully</title>
</head>

<body>
    <h3>Hello {{ $user->name }},</h3>
    <p>You have successfully borrowed the book: <strong>{{ $book->name }}</strong>.</p>
    <p>Enjoy your reading!</p>
</body>

</html>
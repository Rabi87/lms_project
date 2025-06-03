<?php
// Database configuration
$servername = "localhost";
$username = "rabi";
$password = "Asd@123@123";
$dbname = "library_db";


// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch books from database
$sql = "SELECT id, title, author, type, cover_image FROM books";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Compact Book Flip Cards</title>
    <style>
        body {
            margin: 0;
            padding: 20px;
            background:rgb(255, 255, 255);
        }

        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 5px;
            max-width: 1600px;
            margin: 0 auto;
            padding:10px;
        }

        .flip-container {
            perspective: 500px;
            width: 100%;
            height: 200px;
        }

        .flip-inner {
            position: relative;
            width: 100%;
            height: 100%;
            transition: transform 0.6s;
            transform-style: preserve-3d;
        }

        .flip-container:hover .flip-inner {
            transform: rotateY(180deg);
        }

        .front, .back {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .front {
            background: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 10px;
        }

        .back {
            background: #fff;
            transform: rotateY(180deg);
            padding: 15px;
            overflow: hidden;
        }

        .book-cover {
            width: 120px;
            height: 180px;
            object-fit: cover;
            margin-bottom: 10px;
        }

        .book-title {
            font-size: 1em;
            font-weight: 600;
            margin-bottom: 5px;
            text-align: center;
        }

        .book-author {
            font-size: 0.8em;
            color: #666;
            text-align: center;
        }

        .book-description {
            font-size: 0.75em;
            line-height: 1.3;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 7;
            -webkit-box-orient: vertical;
        }

        h1 {
            text-align: center;
            font-size: 1.5em;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <h1>Book Collection</h1>
    
    <div class="cards-container">
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="flip-container">
                    <div class="flip-inner">
                        <div class="front">
                            <img src="<?php echo htmlspecialchars($row['cover_image']); ?>" class="book-cover" alt="Book Cover">
                            <img src="assets/images/books/<?= $book['cover_image'] ?>">
                           
                        </div>
                        <div class="back">
                            <div class="book-title"><?php echo htmlspecialchars($row['title']); ?></div>
                            <div class="book-author"><?php echo htmlspecialchars($row['author']); ?></div>
                            <p class="book-description"><?php echo htmlspecialchars($row['type']); ?></p>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align: center;">No books found in the database</p>
        <?php endif; ?>
    </div>
    
    <?php $conn->close(); ?>
</body>
</html>
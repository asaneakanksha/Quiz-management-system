<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "db.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {

    if ($id > 0) {

        $stmt = $pdo->prepare("
            SELECT 
                qr.id,
                qr.student_name,
                qr.score,
                qr.total_marks,
                qr.percentage,
                qr.attempted_at,
                q.title,
                q.subject
            FROM quiz_result qr
            JOIN quizes q ON qr.quiz_id = q.id
            WHERE qr.id = ?
        ");

        $stmt->execute([$id]);
        $result = $stmt->fetch();

    } else {

        $stmt = $pdo->query("
            SELECT 
                qr.id,
                qr.student_name,
                qr.score,
                qr.total_marks,
                qr.percentage,
                qr.attempted_at,
                q.title,
                q.subject
            FROM quiz_result qr
            JOIN quizes q ON qr.quiz_id = q.id
            ORDER BY qr.attempted_at DESC
        ");

        $results = $stmt->fetchAll();
    }

} catch (PDOException $e) {

    die("Database Error: " . htmlspecialchars($e->getMessage()));

}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Quiz Results</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

<nav class="navbar">

    <div class="brand">
        QuizMaster
    </div>

    <div class="nav-links">
        <a href="build.php">Build Quiz</a>
        <a href="result.php">Results</a>
    </div>

</nav>

<div class="container">

    <div class="card">

        <h1>📊 Quiz Results</h1>

        <?php if ($id > 0): ?>

            <?php if (!$result): ?>

                <h2>Result Not Found</h2>

                <a href="result.php" class="btn">
                    View All Results
                </a>

            <?php else: ?>

                <h2>
                    <?= htmlspecialchars($result['title']) ?>
                </h2>

                <p>
                    <strong>Subject:</strong>
                    <?= htmlspecialchars($result['subject']) ?>
                </p>

                <p>
                    <strong>Student:</strong>
                    <?= htmlspecialchars($result['student_name']) ?>
                </p>

                <div class="result-box">

                    <div>
                        <span>Score</span>

                        <strong>
                            <?= (int)$result['score'] ?>
                            /
                            <?= (int)$result['total_marks'] ?>
                        </strong>
                    </div>

                    <div>
                        <span>Percentage</span>

                        <strong>
                            <?= number_format(
                                (float)$result['percentage'],
                                2
                            ) ?>%
                        </strong>
                    </div>

                </div>

                <p>
                    <strong>Attempted At:</strong>
                    <?= htmlspecialchars($result['attempted_at']) ?>
                </p>

                <br>

                <a href="result.php" class="btn">
                    View All Results
                </a>

                <a href="build.php" class="btn secondary">
                    Back to Quiz Builder
                </a>

            <?php endif; ?>

        <?php else: ?>

            <?php if (empty($results)): ?>

                <p>
                    No quiz results available yet.
                </p>

                <a href="build.php" class="btn">
                    Create Quiz
                </a>

            <?php else: ?>

                <div class="table-container">

                    <table>

                        <thead>

                            <tr>
                                <th>Student</th>
                                <th>Quiz</th>
                                <th>Subject</th>
                                <th>Score</th>
                                <th>Percentage</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>

                        </thead>

                        <tbody>

                        <?php foreach ($results as $row): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars(
                                        $row['student_name']
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $row['title']
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $row['subject']
                                    ) ?>
                                </td>

                                <td>
                                    <?= (int)$row['score'] ?>
                                    /
                                    <?= (int)$row['total_marks'] ?>
                                </td>

                                <td>
                                    <?= number_format(
                                        (float)$row['percentage'],
                                        2
                                    ) ?>%
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $row['attempted_at']
                                    ) ?>
                                </td>

                                <td>

                                    <a
                                        href="result.php?id=<?= (int)$row['id'] ?>"
                                        class="btn small">
                                        View
                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        <?php endif; ?>

    </div>

</div>

</body>
</html>
<?php

require_once "db.php";

$quizId = intval(
    $_GET["quiz_id"] ?? $_POST["quiz_id"] ?? 0
);

if ($quizId <= 0) {
    die("Invalid quiz ID.");
}


/* GET QUIZ */

$stmt = $pdo->prepare(
    "SELECT *
     FROM quizes
     WHERE id = ?"
);

$stmt->execute([
    $quizId
]);

$quiz = $stmt->fetch();


if (!$quiz) {
    die("Quiz not found.");
}


/* GET QUESTIONS */

$stmt = $pdo->prepare(
    "SELECT *
     FROM question
     WHERE quiz_id = ?
     ORDER BY id ASC"
);

$stmt->execute([
    $quizId
]);

$questions =
    $stmt->fetchAll();


if (count($questions) === 0) {
    die("This quiz has no questions.");
}


/* SUBMIT QUIZ */

if ($_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["submit_quiz"])) {

    $studentName =
        trim($_POST["student_name"] ?? "");


    if ($studentName === "") {

        die("Please enter student name.");
    }


    $score = 0;

    $totalMarks = 0;


    foreach ($questions as $q) {

        $totalMarks +=
            intval($q["marks"]);


        $answer =
            $_POST["answer_" . $q["id"]]
            ?? "";


        if ($answer ===
            $q["correct_answer"]) {

            $score +=
                intval($q["marks"]);
        }
    }


    $percentage =
        $totalMarks > 0
        ? ($score / $totalMarks) * 100
        : 0;


    /* SAVE RESULT */

    $stmt = $pdo->prepare(
        "INSERT INTO quiz_result
        (
            quiz_id,
            student_name,
            score,
            total_marks,
            percentage
        )
        VALUES (?, ?, ?, ?, ?)"
    );


    $stmt->execute([

        $quizId,

        $studentName,

        $score,

        $totalMarks,

        $percentage

    ]);


    $resultId =
        $pdo->lastInsertId();


    ?>

    <!DOCTYPE html>

    <html>

    <head>

    <title>Quiz Result</title>

    <link rel="stylesheet"
          href="style.css">

    </head>


    <body>

    <div class="topbar">

        <div class="logo">
            QuizMaster
        </div>

        <div class="nav">

            <a href="build.php">
                Create Quiz
            </a>

            <a href="result.php"
               class="active">
                Results
            </a>

        </div>

    </div>


    <div class="container">

    <div class="card center">

        <h1>
            Quiz Completed!
        </h1>

        <h2>
            <?= htmlspecialchars($quiz["title"]) ?>
        </h2>

        <p>
            Student:
            <strong>
                <?= htmlspecialchars($studentName) ?>
            </strong>
        </p>


        <div class="result">

            <?= $score ?>
            /
            <?= $totalMarks ?>

        </div>


        <h2>

            <?= number_format(
                $percentage,
                2
            ) ?>%

        </h2>


        <br>


        <a href="attempt.php?quiz_id=<?= $quizId ?>"
           class="btn">

            Attempt Again

        </a>


        <a href="result.php?id=<?= $resultId ?>"
           class="btn green">

            View Result

        </a>


        <a href="build.php"
           class="btn gray">

            Back to Quiz Builder

        </a>

    </div>

    </div>

    </body>

    </html>

    <?php

    exit;
}

?>


<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
    <?= htmlspecialchars($quiz["title"]) ?>
</title>

<link rel="stylesheet"
      href="style.css">

</head>


<body>


<div class="topbar">

    <div class="logo">
        QuizMaster
    </div>

    <div class="nav">

        <a href="build.php">
            Create Quiz
        </a>

        <a href="result.php">
            Results
        </a>

    </div>

</div>


<div class="container">


<div class="card">


<h1>
    <?= htmlspecialchars($quiz["title"]) ?>
</h1>


<p>

Subject:

<strong>
    <?= htmlspecialchars($quiz["subject"]) ?>
</strong>

</p>


<form method="POST">


<input type="hidden"
       name="quiz_id"
       value="<?= $quizId ?>">


<div class="group">

<label>
    Student Name
</label>

<input type="text"
       name="student_name"
       placeholder="Enter your name"
       required>

</div>


<?php foreach ($questions as $index => $q): ?>


<div class="q">


<h3>

Q<?= $index + 1 ?>.
<?= htmlspecialchars(
    $q["question_text"]
) ?>

</h3>


<p>

Marks:
<strong>
    <?= $q["marks"] ?>
</strong>

</p>


<?php if ($q["question_type"] === "mcq"): ?>


<label class="quiz-option">

<input type="radio"
       name="answer_<?= $q["id"] ?>"
       value="A">

A.
<?= htmlspecialchars($q["option_a"]) ?>

</label>


<label class="quiz-option">

<input type="radio"
       name="answer_<?= $q["id"] ?>"
       value="B">

B.
<?= htmlspecialchars($q["option_b"]) ?>

</label>


<label class="quiz-option">

<input type="radio"
       name="answer_<?= $q["id"] ?>"
       value="C">

C.
<?= htmlspecialchars($q["option_c"]) ?>

</label>


<label class="quiz-option">

<input type="radio"
       name="answer_<?= $q["id"] ?>"
       value="D">

D.
<?= htmlspecialchars($q["option_d"]) ?>

</label>


<?php else: ?>


<label class="quiz-option">

<input type="radio"
       name="answer_<?= $q["id"] ?>"
       value="True">

True

</label>


<label class="quiz-option">

<input type="radio"
       name="answer_<?= $q["id"] ?>"
       value="False">

False

</label>


<?php endif; ?>


</div>


<?php endforeach; ?>


<button type="submit"
        name="submit_quiz"
        class="btn">

Submit Quiz

</button>


</form>


</div>


</div>


</body>

</html>
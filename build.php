<?php

require_once "db.php";

$message = "";
$error = "";


/* =========================
   SAVE / UPDATE QUIZ
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["save_quiz"])) {

    $title = trim($_POST["title"] ?? "");
    $subject = trim($_POST["subject"] ?? "");

    $questionsJson = $_POST["questions"] ?? "[]";

    if ($title === "" || $subject === "") {

        $error = "Please enter quiz title and subject.";

    } else {

        $questions = json_decode(
            $questionsJson,
            true
        );

        if (!is_array($questions)
            || count($questions) === 0) {

            $error = "Please add at least one question.";

        } else {

            try {

                $pdo->beginTransaction();

                $quizId = intval(
                    $_POST["quiz_id"] ?? 0
                );


                /* UPDATE */

                if ($quizId > 0) {

                    $stmt = $pdo->prepare(
                        "UPDATE quizes
                         SET title = ?, subject = ?
                         WHERE id = ?"
                    );

                    $stmt->execute([
                        $title,
                        $subject,
                        $quizId
                    ]);


                    $stmt = $pdo->prepare(
                        "DELETE FROM question
                         WHERE quiz_id = ?"
                    );

                    $stmt->execute([
                        $quizId
                    ]);

                    $message =
                        "Quiz updated successfully.";

                }


                /* CREATE */

                else {

                    $stmt = $pdo->prepare(
                        "INSERT INTO quizes
                         (title, subject)
                         VALUES (?, ?)"
                    );

                    $stmt->execute([
                        $title,
                        $subject
                    ]);

                    $quizId =
                        $pdo->lastInsertId();

                    $message =
                        "Quiz created successfully.";
                }


                /* INSERT QUESTIONS */

                $stmt = $pdo->prepare(
                    "INSERT INTO question
                    (
                        quiz_id,
                        question_text,
                        question_type,
                        option_a,
                        option_b,
                        option_c,
                        option_d,
                        correct_answer,
                        marks
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );


                foreach ($questions as $q) {

                    $questionText =
                        trim($q["question"] ?? "");

                    $type =
                        $q["type"] ?? "mcq";

                    $optionA =
                        $q["optionA"] ?? null;

                    $optionB =
                        $q["optionB"] ?? null;

                    $optionC =
                        $q["optionC"] ?? null;

                    $optionD =
                        $q["optionD"] ?? null;

                    $correct =
                        $q["correct"] ?? "";

                    $marks =
                        intval($q["marks"] ?? 1);


                    if ($questionText === ""
                        || $correct === "") {

                        continue;
                    }


                    $stmt->execute([
                        $quizId,
                        $questionText,
                        $type,
                        $optionA,
                        $optionB,
                        $optionC,
                        $optionD,
                        $correct,
                        $marks
                    ]);
                }


                $pdo->commit();

            } catch (Exception $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $error =
                    "Error: " . $e->getMessage();
            }
        }
    }
}


/* =========================
   DELETE QUIZ
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["delete_quiz"])) {

    $quizId =
        intval($_POST["quiz_id"] ?? 0);


    if ($quizId > 0) {

        try {

            $stmt = $pdo->prepare(
                "DELETE FROM quizes
                 WHERE id = ?"
            );

            $stmt->execute([
                $quizId
            ]);

            $message =
                "Quiz deleted successfully.";

        } catch (Exception $e) {

            $error =
                "Delete error: "
                . $e->getMessage();
        }
    }
}


/* =========================
   EDIT QUIZ
========================= */

$editQuiz = null;
$editQuestions = [];

if (isset($_GET["edit"])) {

    $editId =
        intval($_GET["edit"]);


    $stmt = $pdo->prepare(
        "SELECT *
         FROM quizes
         WHERE id = ?"
    );

    $stmt->execute([
        $editId
    ]);

    $editQuiz =
        $stmt->fetch();


    if ($editQuiz) {

        $stmt = $pdo->prepare(
            "SELECT *
             FROM question
             WHERE quiz_id = ?
             ORDER BY id ASC"
        );

        $stmt->execute([
            $editId
        ]);

        $editQuestions =
            $stmt->fetchAll();
    }
}


/* =========================
   GET QUIZZES
========================= */

$stmt = $pdo->query(
    "SELECT
        q.id,
        q.title,
        q.subject,
        q.created_at,
        COUNT(que.id) AS question_count,
        COALESCE(SUM(que.marks),0)
            AS total_marks

     FROM quizes q

     LEFT JOIN question que
        ON q.id = que.quiz_id

     GROUP BY q.id

     ORDER BY q.id DESC"
);

$quizzes =
    $stmt->fetchAll();

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Quiz Builder</title>

<link rel="stylesheet"
      href="style.css">

</head>


<body>


<!-- NAVBAR -->

<div class="topbar">

    <div class="logo">
        QuizMaster
    </div>

    <div class="nav">

        <a href="build.php"
           class="active">
            Create Quiz
        </a>

        <a href="result.php">
            Results
        </a>

    </div>

</div>



<div class="container">

<h1>
    Quiz Builder
</h1>


<?php if ($message): ?>

<div class="success">

<?= htmlspecialchars($message) ?>

</div>

<?php endif; ?>


<?php if ($error): ?>

<div class="error">

<?= htmlspecialchars($error) ?>

</div>

<?php endif; ?>



<div class="grid">


<!-- LEFT -->

<div class="card">

<h2>

<?= $editQuiz
    ? "Edit Quiz"
    : "Create New Quiz" ?>

</h2>


<form method="POST"
      id="quizForm">


<input type="hidden"
       name="quiz_id"
       value="<?= $editQuiz
           ? $editQuiz["id"]
           : 0 ?>">


<input type="hidden"
       name="questions"
       id="questionsInput">



<div class="group">

<label>
    Quiz Title
</label>

<input type="text"
       name="title"
       id="title"
       placeholder="Enter quiz title"
       value="<?= $editQuiz
           ? htmlspecialchars($editQuiz["title"])
           : "" ?>"
       required>

</div>



<div class="group">

<label>
    Subject
</label>

<input type="text"
       name="subject"
       id="subject"
       placeholder="Enter subject"
       value="<?= $editQuiz
           ? htmlspecialchars($editQuiz["subject"])
           : "" ?>"
       required>

</div>


<hr>


<h3>
    Add Question
</h3>


<div class="group">

<label>
    Question
</label>

<textarea
    id="questionText"
    placeholder="Enter question">
</textarea>

</div>


<div class="group">

<label>
    Question Type
</label>

<select id="questionType">

<option value="mcq">
    Multiple Choice
</option>

<option value="truefalse">
    True / False
</option>

</select>

</div>


<!-- MCQ -->

<div id="mcqOptions">


<div class="group">

<label>
    Option A
</label>

<input type="text"
       id="optionA"
       placeholder="Option A">

</div>


<div class="group">

<label>
    Option B
</label>

<input type="text"
       id="optionB"
       placeholder="Option B">

</div>


<div class="group">

<label>
    Option C
</label>

<input type="text"
       id="optionC"
       placeholder="Option C">

</div>


<div class="group">

<label>
    Option D
</label>

<input type="text"
       id="optionD"
       placeholder="Option D">

</div>


<div class="group">

<label>
    Correct Option
</label>

<select id="correctAnswer">

<option value="">
    Select Correct Option
</option>

<option value="A">
    Option A
</option>

<option value="B">
    Option B
</option>

<option value="C">
    Option C
</option>

<option value="D">
    Option D
</option>

</select>

</div>

</div>


<!-- TRUE FALSE -->

<div id="trueFalseOptions"
     style="display:none;">

<div class="group">

<label>
    Correct Answer
</label>

<select id="trueFalseAnswer">

<option value="">
    Select Answer
</option>

<option value="True">
    True
</option>

<option value="False">
    False
</option>

</select>

</div>

</div>


<div class="group">

<label>
    Marks
</label>

<input type="number"
       id="marks"
       value="1"
       min="1">

</div>


<button type="button"
        class="btn"
        onclick="addQuestion()">

Add Question

</button>


</form>

</div>



<!-- QUESTIONS -->

<div class="card">

<h2>
    Questions
</h2>

<div id="questionList">

<p>
    No questions added.
</p>

</div>

</div>



<button type="submit"
        form="quizForm"
        name="save_quiz"
        class="btn">

<?= $editQuiz
    ? "Update Quiz"
    : "Save Quiz" ?>

</button>


<?php if ($editQuiz): ?>

<a href="build.php"
   class="btn gray">

Cancel

</a>

<?php endif; ?>


<!-- PREVIEW -->

<div class="card">

<h2>
    Quiz Preview
</h2>

<div class="preview"
     id="preview">

<p>
    Add questions to see preview.
</p>

</div>

</div>


</div>


<!-- EXISTING QUIZZES -->

<div class="card">

<h2>
    Existing Quizzes
</h2>


<div class="table-wrap">

<table>

<thead>

<tr>

<th>ID</th>
<th>Title</th>
<th>Subject</th>
<th>Questions</th>
<th>Marks</th>
<th>Actions</th>

</tr>

</thead>


<tbody>


<?php if (!$quizzes): ?>

<tr>

<td colspan="6">

No quizzes found.

</td>

</tr>

<?php endif; ?>


<?php foreach ($quizzes as $quiz): ?>

<tr>

<td>
<?= $quiz["id"] ?>
</td>

<td>
<?= htmlspecialchars($quiz["title"]) ?>
</td>

<td>
<?= htmlspecialchars($quiz["subject"]) ?>
</td>

<td>
<?= $quiz["question_count"] ?>
</td>

<td>
<?= $quiz["total_marks"] ?>
</td>

<td>

<div class="actions">


<a href="build.php?edit=<?= $quiz["id"] ?>"
   class="btn">

Edit

</a>


<a href="attempt.php?quiz_id=<?= $quiz["id"] ?>"
   class="btn green">

Attempt

</a>


<form method="POST"
      style="display:inline;"
      onsubmit="return confirm('Delete this quiz?');">

<input type="hidden"
       name="quiz_id"
       value="<?= $quiz["id"] ?>">


<button type="submit"
        name="delete_quiz"
        class="btn red">

Delete

</button>

</form>


</div>

</td>

</tr>

<?php endforeach; ?>


</tbody>

</table>

</div>

</div>


</div>



<script>

let questions = [];

let editIndex = -1;


/* LOAD EDIT DATA */

<?php if (!empty($editQuestions)): ?>

questions = <?= json_encode(

array_map(function ($q) {

    return [

        "question" =>
            $q["question_text"],

        "type" =>
            $q["question_type"],

        "optionA" =>
            $q["option_a"],

        "optionB" =>
            $q["option_b"],

        "optionC" =>
            $q["option_c"],

        "optionD" =>
            $q["option_d"],

        "correct" =>
            $q["correct_answer"],

        "marks" =>
            intval($q["marks"])

    ];

}, $editQuestions),

JSON_UNESCAPED_UNICODE

) ?>;

<?php endif; ?>


/* TYPE CHANGE */

document
.getElementById("questionType")
.addEventListener("change", function() {

    if (this.value === "mcq") {

        document
        .getElementById("mcqOptions")
        .style.display = "block";

        document
        .getElementById("trueFalseOptions")
        .style.display = "none";

    } else {

        document
        .getElementById("mcqOptions")
        .style.display = "none";

        document
        .getElementById("trueFalseOptions")
        .style.display = "block";

    }

});


/* ADD QUESTION */

function addQuestion() {

    let question =
        document
        .getElementById("questionText")
        .value.trim();


    let type =
        document
        .getElementById("questionType")
        .value;


    let marks =
        parseInt(
            document
            .getElementById("marks")
            .value
        );


    if (!question) {

        alert("Please enter question.");

        return;
    }


    if (!marks || marks < 1) {

        alert("Marks must be at least 1.");

        return;
    }


    let q = {

        question: question,

        type: type,

        optionA: "",
        optionB: "",
        optionC: "",
        optionD: "",

        correct: "",

        marks: marks

    };


    if (type === "mcq") {

        q.optionA =
            document
            .getElementById("optionA")
            .value.trim();

        q.optionB =
            document
            .getElementById("optionB")
            .value.trim();

        q.optionC =
            document
            .getElementById("optionC")
            .value.trim();

        q.optionD =
            document
            .getElementById("optionD")
            .value.trim();


        q.correct =
            document
            .getElementById("correctAnswer")
            .value;


        if (!q.optionA ||
            !q.optionB ||
            !q.optionC ||
            !q.optionD) {

            alert(
                "Please enter all four options."
            );

            return;
        }


        if (!q.correct) {

            alert(
                "Please select correct option."
            );

            return;
        }

    } else {

        q.correct =
            document
            .getElementById("trueFalseAnswer")
            .value;


        if (!q.correct) {

            alert(
                "Please select True or False."
            );

            return;
        }
    }


    if (editIndex >= 0) {

        questions[editIndex] = q;

        editIndex = -1;

    } else {

        questions.push(q);

    }


    clearForm();

    renderQuestions();

}


/* CLEAR */

function clearForm() {

    document
    .getElementById("questionText")
    .value = "";

    document
    .getElementById("optionA")
    .value = "";

    document
    .getElementById("optionB")
    .value = "";

    document
    .getElementById("optionC")
    .value = "";

    document
    .getElementById("optionD")
    .value = "";

    document
    .getElementById("correctAnswer")
    .value = "";

    document
    .getElementById("trueFalseAnswer")
    .value = "";

    document
    .getElementById("marks")
    .value = 1;

}


/* DELETE */

function deleteQuestion(index) {

    if (confirm("Delete this question?")) {

        questions.splice(index, 1);

        renderQuestions();
    }

}


/* EDIT */

function editQuestion(index) {

    let q = questions[index];

    editIndex = index;


    document
    .getElementById("questionText")
    .value = q.question;


    document
    .getElementById("questionType")
    .value = q.type;


    document
    .getElementById("optionA")
    .value = q.optionA || "";


    document
    .getElementById("optionB")
    .value = q.optionB || "";


    document
    .getElementById("optionC")
    .value = q.optionC || "";


    document
    .getElementById("optionD")
    .value = q.optionD || "";


    document
    .getElementById("correctAnswer")
    .value = q.correct || "";


    document
    .getElementById("trueFalseAnswer")
    .value = q.correct || "";


    document
    .getElementById("marks")
    .value = q.marks;


    document
    .getElementById("questionType")
    .dispatchEvent(
        new Event("change")
    );

}


/* RENDER */

function renderQuestions() {

    let list =
        document
        .getElementById("questionList");


    if (questions.length === 0) {

        list.innerHTML =
            "<p>No questions added.</p>";

        updatePreview();

        return;
    }


    let html = "";


    questions.forEach(function(q, index) {

        html += `

        <div class="q">

            <strong>
                Q${index + 1}.
                ${escapeHTML(q.question)}
            </strong>

            <br>

            <small>
                Marks: ${q.marks}
            </small>

            <div class="actions"
                 style="margin-top:10px;">

                <button
                    type="button"
                    class="btn"
                    onclick="editQuestion(${index})">

                    Edit

                </button>

                <button
                    type="button"
                    class="btn red"
                    onclick="deleteQuestion(${index})">

                    Delete

                </button>

            </div>

        </div>

        `;

    });


    list.innerHTML = html;

    updatePreview();

}


/* PREVIEW */

function updatePreview() {

    let preview =
        document
        .getElementById("preview");


    if (questions.length === 0) {

        preview.innerHTML =
            "<p>Add questions to see preview.</p>";

        return;
    }


    let html = "";


    questions.forEach(function(q, index) {

        html += `

        <div class="q">

            <strong>
                Q${index + 1}.
                ${escapeHTML(q.question)}
            </strong>

        `;


        if (q.type === "mcq") {

            html += `

            <div class="option">
                A. ${escapeHTML(q.optionA)}
            </div>

            <div class="option">
                B. ${escapeHTML(q.optionB)}
            </div>

            <div class="option">
                C. ${escapeHTML(q.optionC)}
            </div>

            <div class="option">
                D. ${escapeHTML(q.optionD)}
            </div>

            `;

        } else {

            html += `

            <div class="option">
                True
            </div>

            <div class="option">
                False
            </div>

            `;

        }


        html += `

        <small>
            Marks: ${q.marks}
        </small>

        </div>

        `;

    });


    preview.innerHTML = html;

}


/* ESCAPE */

function escapeHTML(text) {

    if (!text) return "";

    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");

}


/* SUBMIT */

document
.getElementById("quizForm")
.addEventListener("submit", function(e) {

    if (questions.length === 0) {

        e.preventDefault();

        alert(
            "Please add at least one question."
        );

        return;
    }


    document
    .getElementById("questionsInput")
    .value =
        JSON.stringify(questions);

});


/* INITIAL */

renderQuestions();

</script>


</body>

</html>
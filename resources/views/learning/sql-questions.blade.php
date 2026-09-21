@extends('layouts.app')

@section('title', $module['title'].' — '.config('platform.name'))

@push('styles')

@include('learning.partials.three-column-styles')

<style>
    /* =========================================================
       SQL LEARNING PAGE
    ========================================================= */

    .topic-sidebar {
        grid-column: 1;
    }

    .questions-main {
        grid-column: 2;
    }

    .course-sidebar {
        grid-column: 3;
    }

    @media(max-width:1100px) {
        .course-sidebar {
            grid-column: 1/-1;
        }

        .questions-main {
            grid-column: 2;
        }
    }

    @media(max-width:760px) {

        .topic-sidebar,
        .questions-main,
        .course-sidebar {
            grid-column: 1;
        }

        .topic-sidebar {
            order: 1;
        }

        .questions-main {
            order: 2;
        }

        .course-sidebar {
            order: 3;
        }
    }

    /* =========================================================
       SQL INTRODUCTION
    ========================================================= */

    .sql-learning-intro {
        background: linear-gradient(135deg, #062e2b, #0f766e);
        color: #fff;
        padding: 28px;
        border-radius: 18px;
        margin-bottom: 24px;
        box-shadow: 0 12px 35px rgba(15, 118, 110, .16);
    }

    .sql-learning-intro h2 {
        margin: 0 0 10px;
        font-size: 28px;
    }

    .sql-learning-intro p {
        margin: 0;
        max-width: 850px;
        line-height: 1.7;
        opacity: .94;
    }

    .sql-learning-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 18px;
    }

    .sql-learning-pill {
        padding: 7px 12px;
        border-radius: 999px;
        background: rgba(255, 255, 255, .12);
        border: 1px solid rgba(255, 255, 255, .18);
        font-size: 13px;
    }

    /* =========================================================
       SEARCH
    ========================================================= */

    .sql-question-search {
        position: sticky;
        top: 12px;
        z-index: 20;
        background: rgba(255, 255, 255, .96);
        backdrop-filter: blur(10px);
        border: 1px solid #dbe5e4;
        border-radius: 14px;
        padding: 12px;
        margin-bottom: 20px;
        box-shadow: 0 8px 25px rgba(15, 23, 42, .06);
    }

    .sql-question-search input {
        width: 100%;
        box-sizing: border-box;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        padding: 13px 15px;
        font-size: 15px;
        outline: none;
    }

    .sql-question-search input:focus {
        border-color: #0f766e;
        box-shadow: 0 0 0 3px rgba(15, 118, 110, .10);
    }

    .sql-search-result {
        display: none;
        margin-top: 8px;
        font-size: 13px;
        color: #64748b;
    }

    /* =========================================================
       CONCEPT SECTION
    ========================================================= */

    .sql-concept-card {
        margin: 0 0 24px;
        padding: 26px;
        border: 1px solid #dbe5e4;
        border-radius: 18px;
        background: #fff;
    }

    .sql-concept-label {
        display: inline-block;
        margin-bottom: 8px;
        color: #0f766e;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .sql-concept-card h2 {
        margin: 0 0 10px;
        color: #172033;
    }

    .sql-concept-card p {
        color: #526173;
        line-height: 1.75;
    }

    /* =========================================================
       PRACTICAL DATABASE EXAMPLES
    ========================================================= */

    .sql-example-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
        margin-top: 18px;
    }

    .sql-example {
        border: 1px solid #dbe5e4;
        border-radius: 14px;
        padding: 18px;
        background: #f8fafc;
    }

    .sql-example h3 {
        margin: 0 0 8px;
        font-size: 17px;
        color: #172033;
    }

    .sql-example p {
        margin: 0 0 12px;
        font-size: 14px;
    }

    .sql-code {
        overflow-x: auto;
        background: #0f172a;
        color: #e2e8f0;
        padding: 15px;
        border-radius: 10px;
        font: 13px/1.65 Consolas, Monaco, monospace;
    }

    .sql-table-wrap {
        overflow-x: auto;
        margin-top: 15px;
    }

    .sql-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
        background: #fff;
    }

    .sql-table th,
    .sql-table td {
        padding: 10px 12px;
        border: 1px solid #dbe5e4;
        text-align: left;
    }

    .sql-table th {
        background: #ecfdf5;
        color: #065f46;
    }

    /* =========================================================
       QUESTION
    ========================================================= */

    .sql-question-group {
        margin-top: 30px;
    }

    .sql-group-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 14px;
        padding-bottom: 12px;
        border-bottom: 1px solid #dbe5e4;
    }

    .sql-group-heading h2 {
        margin: 0;
        color: #172033;
    }

    .sql-group-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #ccfbf1;
        color: #0f766e;
        font-weight: 800;
    }

    .sql-card {
        margin: 0 0 16px;
        padding: 25px;
        border: 1px solid #dbe5e4;
        border-radius: 15px;
        background: #fff;
        transition: .2s ease;
    }

    .sql-card:hover {
        border-color: #99f6e4;
        box-shadow: 0 8px 25px rgba(15, 118, 110, .08);
    }

    .sql-card.is-hidden-by-search {
        display: none;
    }

    .sql-number {
        color: #0f766e;
        font-weight: 800;
        font-size: 13px;
    }

    .sql-card h2 {
        margin-top: 9px;
        color: #172033;
        line-height: 1.45;
    }

    .sql-answer {
        margin-top: 14px;
        padding: 18px;
        border-left: 4px solid #0f766e;
        border-radius: 0 10px 10px 0;
        background: #f0fdfa;
        white-space: pre-wrap;
        color: #25364a;
        font: 15px/1.7 Inter, system-ui;
    }

    .sql-card:target {
        border-color: #0f766e;
        box-shadow: 0 8px 24px #0f766e20;
    }

    /* =========================================================
       QUIZ
    ========================================================= */

    .sql-quiz {
        margin: 28px 0 34px;
        padding: 25px;
        border-radius: 18px;
        background: linear-gradient(135deg, #f0fdfa, #ecfeff);
        border: 1px solid #99f6e4;
    }

    .sql-quiz-header {
        margin-bottom: 18px;
    }

    .sql-quiz-label {
        color: #0f766e;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .sql-quiz-header h2 {
        margin: 5px 0;
        color: #134e4a;
    }

    .sql-quiz-header p {
        margin: 0;
        color: #526173;
    }

    .sql-quiz-question {
        padding: 18px;
        margin-bottom: 12px;
        background: #fff;
        border: 1px solid #dbe5e4;
        border-radius: 12px;
    }

    .sql-quiz-question strong {
        display: block;
        margin-bottom: 12px;
        color: #172033;
    }

    .sql-quiz-options {
        display: grid;
        gap: 8px;
    }

    .sql-quiz-option {
        display: block;
        cursor: pointer;
        padding: 10px 12px;
        border: 1px solid #dbe5e4;
        border-radius: 9px;
        background: #f8fafc;
    }

    .sql-quiz-option:hover {
        border-color: #5eead4;
        background: #f0fdfa;
    }

    .sql-quiz-result {
        display: none;
        margin-top: 15px;
        padding: 15px;
        border-radius: 10px;
        background: #fff;
        font-weight: 700;
    }

    .sql-quiz-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 18px;
    }

    .sql-quiz-button {
        border: 0;
        border-radius: 9px;
        padding: 11px 17px;
        cursor: pointer;
        font-weight: 700;
        background: #0f766e;
        color: #fff;
    }

    .sql-quiz-button.secondary {
        background: #e2e8f0;
        color: #334155;
    }

    /* =========================================================
       RECALL
    ========================================================= */

    .vl-mode {
        display: block;
        margin: 14px 0 18px;
        padding: 12px;
        border-radius: 10px;
        background: #f8fafc;
        color: #475569;
    }

    /* =========================================================
       RESPONSIVE
    ========================================================= */

    @media(max-width:760px) {

        .sql-example-grid {
            grid-template-columns: 1fr;
        }

        .sql-learning-intro,
        .sql-concept-card,
        .sql-card,
        .sql-quiz {
            padding: 18px;
        }
    }
</style>

@endpush


@section('content')

<section class="hero-band">
    <div class="container">

        <a href="{{ route('learning.track', $trackSlug) }}"
            style="color:#bfdbfe">

            ← SQL learning path

        </a>

        <h1>{{ $module['title'] }}</h1>

        <p>
            Learn SQL from fundamentals to advanced interview and
            production-level database concepts through practical examples,
            questions and quizzes.
        </p>

    </div>
</section>


<div class="learning-shell">

    @include('learning.partials.module-sidebars')


    <main class="questions-main">


        {{-- =====================================================
             SQL LEARNING INTRODUCTION
        ====================================================== --}}

        <section class="sql-learning-intro">

            <h2>Complete SQL Learning & Interview Path</h2>

            <p>
                Learn SQL by working with realistic databases instead of
                memorising isolated commands. Every concept can be connected
                with practical tables such as students, employees,
                departments, countries, states, customers, products and orders.
            </p>

            <div class="sql-learning-pills">

                <span class="sql-learning-pill">SQL Fundamentals</span>
                <span class="sql-learning-pill">SELECT</span>
                <span class="sql-learning-pill">WHERE</span>
                <span class="sql-learning-pill">JOINs</span>
                <span class="sql-learning-pill">GROUP BY</span>
                <span class="sql-learning-pill">Subqueries</span>
                <span class="sql-learning-pill">Indexes</span>
                <span class="sql-learning-pill">Transactions</span>
                <span class="sql-learning-pill">Window Functions</span>
                <span class="sql-learning-pill">Interview Questions</span>

            </div>

        </section>


        {{-- =====================================================
             QUESTION SEARCH
        ====================================================== --}}

        <div class="sql-question-search">

            <input
                type="search"
                id="sqlQuestionSearch"
                placeholder="Search SQL questions... JOIN, GROUP BY, index, transaction, employee, student..."
                autocomplete="off">

            <div
                id="sqlSearchResult"
                class="sql-search-result">
            </div>

        </div>


        {{-- =====================================================
             EXISTING VISUAL OVERVIEW
        ====================================================== --}}

        @include('learning.partials.visual-overview')


        {{-- =====================================================
             SQL FUNDAMENTALS
        ====================================================== --}}

        <section class="sql-concept-card">

            <span class="sql-concept-label">
                Start Here
            </span>

            <h2>SQL through a real database</h2>

            <p>
                SQL is used to store, retrieve, modify and analyse structured
                data. Instead of learning commands independently, imagine that
                we are building a small company database.
            </p>


            <div class="sql-example-grid">


                {{-- STUDENT --}}

                <div class="sql-example">

                    <h3>Student table</h3>

                    <p>
                        A student database can contain identity, name,
                        course and marks.
                    </p>

                    <div class="sql-code">CREATE TABLE students (
                        id INT PRIMARY KEY,
                        name VARCHAR(100),
                        course VARCHAR(100),
                        marks INT
                        );</div>

                </div>


                {{-- EMPLOYEE --}}

                <div class="sql-example">

                    <h3>Employee table</h3>

                    <p>
                        Employee information can be connected with departments,
                        salaries and managers.
                    </p>

                    <div class="sql-code">CREATE TABLE employees (
                        id INT PRIMARY KEY,
                        name VARCHAR(100),
                        department_id INT,
                        salary DECIMAL(12,2)
                        );</div>

                </div>


                {{-- COUNTRY --}}

                <div class="sql-example">

                    <h3>Country / State table</h3>

                    <p>
                        Geographic data is useful for understanding
                        relationships and JOINs.
                    </p>

                    <div class="sql-code">CREATE TABLE states (
                        id INT PRIMARY KEY,
                        name VARCHAR(100),
                        country_id INT
                        );</div>

                </div>


                {{-- ORDERS --}}

                <div class="sql-example">

                    <h3>Customer / Orders</h3>

                    <p>
                        E-commerce data provides practical examples for
                        aggregation, JOINs and transactions.
                    </p>

                    <div class="sql-code">CREATE TABLE orders (
                        id INT PRIMARY KEY,
                        customer_id INT,
                        total DECIMAL(12,2),
                        order_date DATE
                        );</div>

                </div>

            </div>


            <div class="sql-table-wrap">

                <table class="sql-table">

                    <thead>
                        <tr>
                            <th>id</th>
                            <th>name</th>
                            <th>course</th>
                            <th>marks</th>
                        </tr>
                    </thead>

                    <tbody>

                        <tr>
                            <td>1</td>
                            <td>Rahul</td>
                            <td>Java</td>
                            <td>85</td>
                        </tr>

                        <tr>
                            <td>2</td>
                            <td>Priya</td>
                            <td>SQL</td>
                            <td>92</td>
                        </tr>

                        <tr>
                            <td>3</td>
                            <td>Amit</td>
                            <td>React</td>
                            <td>76</td>
                        </tr>

                    </tbody>

                </table>

            </div>

        </section>


        {{-- =====================================================
             RECALL MODE
        ====================================================== --}}

        <label class="vl-mode">

            <input
                type="checkbox"
                data-vl-recall-mode>

            Recall mode:
            hide answers until I reveal them

        </label>


        {{-- =====================================================
             EXISTING QUESTIONS
        ====================================================== --}}

        @php
        $questionNumber = 0;
        $questionItems = method_exists($questions, 'items') ? $questions->items() : $questions;
        $questionCount = count($questionItems);
        @endphp


        @foreach($questionItems as $item)

        @php
        $questionNumber++;
        $quizNumber = (int) ceil($questionNumber / 10);

        /*
        * Every 10 existing questions become one learning block.
        */
        $isQuizQuestion = $questionNumber % 10 === 0;
        @endphp


        {{-- SECTION HEADING EVERY 10 QUESTIONS --}}

        @if(($questionNumber - 1) % 10 === 0)

        <section class="sql-question-group">

            <div class="sql-group-heading">

                <div>

                    <div
                        class="sql-number">
                        SQL Learning Block {{ $quizNumber }}
                    </div>

                    <h2>
                        Questions
                        {{ $questionNumber }}
                        –
                        {{ min($questionNumber + 9, $questionCount) }}
                    </h2>

                </div>

                <span class="sql-group-number">
                    {{ $quizNumber }}
                </span>

            </div>

        </section>

        @endif


        {{-- =================================================
                 QUESTION
            ================================================== --}}

        <article
            class="sql-card"
            id="question-{{ $item['number'] }}"
            data-sql-question>

            <span class="sql-number">

                Question
                {{ $item['number'] }}
                @if($questionCount)
                of {{ $questionCount }}
                @endif

            </span>


            <h2 data-sql-question-title>
                {{ $item['question'] }}
            </h2>


            <button
                type="button"
                class="primary"
                data-vl-reveal="answer-{{ $item['number'] }}"
                hidden>

                Reveal explanation

            </button>


            <div
                class="vl-answer"
                id="answer-{{ $item['number'] }}">

                <h3>Solution</h3>

                @include(
                'learning.partials.answer-blocks',
                ['answerText' => $item['answer']]
                )

            </div>


            <details class="vl-recall">

                <summary>
                    Think before looking at the solution
                </summary>

                <ul class="vl-points">

                    <li>
                        What would the SQL query do?
                    </li>

                    <li>
                        Which rows will match?
                    </li>

                    <li>
                        Which columns will appear?
                    </li>

                    <li>
                        What happens when NULL values are involved?
                    </li>

                    <li>
                        Can the query be improved using an index?
                    </li>

                </ul>


                <label class="vl-notes">

                    Your prediction

                    <textarea
                        data-vl-notes="question-{{ $item['number'] }}"></textarea>

                    <span class="vl-note-status">
                        Private practice notes on this browser.
                    </span>

                </label>

            </details>

        </article>


        {{-- =================================================
                 QUIZ AFTER EVERY 10 QUESTIONS
            ================================================== --}}

        @if($questionNumber % 10 === 0)

        @php

        $startIndex = $questionNumber - 10;

        $quizQuestions = array_slice(
        $questionItems,
        $startIndex,
        10
        );

        @endphp


        <section
            class="sql-quiz"
            data-sql-quiz>

            <div class="sql-quiz-header">

                <span class="sql-quiz-label">
                    Knowledge Check
                </span>

                <h2>
                    Quiz after Questions
                    {{ $questionNumber - 9 }}
                    –
                    {{ $questionNumber }}
                </h2>

                <p>
                    Test yourself before moving to the next SQL
                    learning block.
                </p>

            </div>


            @foreach($quizQuestions as $quizIndex => $quizItem)

            @php
            $quizQuestionNumber = $quizIndex + 1;

            /*
            * The first option is the existing answer.
            * The remaining options are deliberately
            * generated from other existing answers so
            * the quiz remains connected to the question bank.
            */
            $correctAnswer = trim(strip_tags($quizItem['answer']));

            $otherAnswers = collect($quizQuestions)
            ->reject(function ($candidate) use ($quizItem) {
            return ($candidate['number'] ?? null)
            === ($quizItem['number'] ?? null);
            })
            ->pluck('answer')
            ->map(function ($answer) {
            return trim(strip_tags($answer));
            })
            ->filter()
            ->unique()
            ->take(3)
            ->values()
            ->all();

            $options = array_merge(
            [$correctAnswer],
            $otherAnswers
            );

            shuffle($options);

            @endphp


            <div
                class="sql-quiz-question"
                data-quiz-question>

                <strong>

                    {{ $quizQuestionNumber }}.
                    {{ $quizItem['question'] }}

                </strong>


                <div class="sql-quiz-options">

                    @foreach($options as $optionIndex => $option)

                    <label class="sql-quiz-option">

                        <input
                            type="radio"
                            name="quiz_{{ $questionNumber }}_{{ $quizQuestionNumber }}"
                            value="{{ $option === $correctAnswer ? 'correct' : 'wrong' }}">

                        {{ \Illuminate\Support\Str::limit($option, 300) }}

                    </label>

                    @endforeach

                </div>

            </div>

            @endforeach


            <div class="sql-quiz-actions">

                <button
                    type="button"
                    class="sql-quiz-button"
                    data-check-sql-quiz>

                    Check Quiz

                </button>


                <button
                    type="button"
                    class="sql-quiz-button secondary"
                    data-reset-sql-quiz>

                    Reset

                </button>

            </div>


            <div
                class="sql-quiz-result"
                data-quiz-result>
            </div>

        </section>

        @endif

        @endforeach


        {{-- =====================================================
             IF TOTAL QUESTIONS ARE NOT A MULTIPLE OF 10
        ====================================================== --}}

        @if($questionCount > 0 && $questionCount % 10 !== 0)

        @php

        $remainingStart = $questionCount - ($questionCount % 10);

        $remainingQuestions = array_slice(
        $questionItems,
        $remainingStart
        );

        @endphp


        <section class="sql-quiz">

            <div class="sql-quiz-header">

                <span class="sql-quiz-label">
                    Knowledge Check
                </span>

                <h2>
                    Quiz — Final Learning Block
                </h2>

                <p>
                    Complete the remaining questions before finishing
                    this SQL learning path.
                </p>

            </div>


            @foreach($remainingQuestions as $quizIndex => $quizItem)

            <div class="sql-quiz-question">

                <strong>
                    {{ $quizIndex + 1 }}.
                    {{ $quizItem['question'] }}
                </strong>

                <details>

                    <summary>
                        Show explanation
                    </summary>

                    <div class="sql-answer">

                        @include(
                        'learning.partials.answer-blocks',
                        ['answerText' => $quizItem['answer']]
                        )

                    </div>

                </details>

            </div>

            @endforeach

        </section>

        @endif


        {{-- =====================================================
             PAGINATION
        ====================================================== --}}

        @include('learning.partials.question-pagination')

    </main>

</div>


@push('scripts')

<script>
    document.addEventListener('DOMContentLoaded', function() {

        /*
         * =========================================================
         * SQL QUESTION SEARCH
         * =========================================================
         */

        const searchInput =
            document.getElementById('sqlQuestionSearch');

        const searchResult =
            document.getElementById('sqlSearchResult');

        const questionCards =
            document.querySelectorAll('[data-sql-question]');


        if (searchInput) {

            searchInput.addEventListener('input', function() {

                const search =
                    this.value
                    .trim()
                    .toLowerCase();

                let visible = 0;


                questionCards.forEach(function(card) {

                    const title =
                        card.querySelector(
                            '[data-sql-question-title]'
                        );

                    const question =
                        title ?
                        title.textContent.toLowerCase() :
                        '';


                    const answer =
                        card.textContent.toLowerCase();


                    const matches = !search ||
                        question.includes(search) ||
                        answer.includes(search);


                    card.classList.toggle(
                        'is-hidden-by-search',
                        !matches
                    );


                    if (matches) {
                        visible++;
                    }

                });


                if (search) {

                    searchResult.style.display = 'block';

                    searchResult.textContent =
                        visible +
                        ' SQL question(s) found on this learning page.';

                } else {

                    searchResult.style.display = 'none';

                }

            });

        }


        /*
         * =========================================================
         * QUIZ
         * =========================================================
         */

        document
            .querySelectorAll('[data-sql-quiz]')
            .forEach(function(quiz) {


                const checkButton =
                    quiz.querySelector(
                        '[data-check-sql-quiz]'
                    );


                const resetButton =
                    quiz.querySelector(
                        '[data-reset-sql-quiz]'
                    );


                const result =
                    quiz.querySelector(
                        '[data-quiz-result]'
                    );


                if (checkButton) {

                    checkButton.addEventListener(
                        'click',
                        function() {

                            const questions =
                                quiz.querySelectorAll(
                                    '[data-quiz-question]'
                                );


                            let score = 0;

                            let answered = 0;


                            questions.forEach(
                                function(question) {

                                    const selected =
                                        question.querySelector(
                                            'input[type="radio"]:checked'
                                        );


                                    if (!selected) {
                                        return;
                                    }


                                    answered++;


                                    if (
                                        selected.value ===
                                        'correct'
                                    ) {

                                        score++;

                                    }

                                }
                            );


                            result.style.display = 'block';


                            if (!answered) {

                                result.textContent =
                                    'Please answer the questions before checking the quiz.';

                                return;

                            }


                            result.textContent =
                                'You scored ' +
                                score +
                                ' out of ' +
                                questions.length +
                                '.';


                            if (
                                score === questions.length
                            ) {

                                result.textContent +=
                                    ' Excellent — you completed this SQL block.';

                            } else if (
                                score >=
                                Math.ceil(
                                    questions.length * .7
                                )
                            ) {

                                result.textContent +=
                                    ' Good work. Review the questions you missed and continue.';

                            } else {

                                result.textContent +=
                                    ' Review this learning block before moving forward.';

                            }

                        }
                    );

                }


                if (resetButton) {

                    resetButton.addEventListener(
                        'click',
                        function() {

                            quiz
                                .querySelectorAll(
                                    'input[type="radio"]'
                                )
                                .forEach(function(input) {

                                    input.checked = false;

                                });


                            result.style.display = 'none';

                            result.textContent = '';

                        }
                    );

                }

            });

    });
</script>

@endpush

@endsection

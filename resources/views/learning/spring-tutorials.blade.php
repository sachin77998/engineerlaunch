@extends('layouts.app')

@section('title', $module['title'].' — '.config('platform.name'))

@push('styles')

<style>
    /* =========================================================
       SPRING LEARNING PAGE
    ========================================================= */

    .spring-hero {
        padding: 52px 20px;
        color: #fff;
        background:
            radial-gradient(circle at 83% 20%,
                #22c55e44,
                transparent 30%),
            linear-gradient(125deg,
                #0f2b24,
                #14532d);
    }

    .course-wrap {
        width: min(1180px, calc(100% - 32px));
        margin: auto;
    }

    .spring-hero a {
        color: #bbf7d0;
    }

    .spring-hero h1 {
        margin: 18px 0 8px;
        font-size: clamp(34px, 5vw, 52px);
    }

    .spring-hero p {
        max-width: 900px;
        line-height: 1.7;
    }

    .flow {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
        margin-top: 22px;
    }

    .flow span {
        padding: 8px 12px;
        border: 1px solid #ffffff2b;
        border-radius: 9px;
        background: #ffffff12;
    }

    /* =========================================================
       COURSE
    ========================================================= */

    .course {
        display: grid;
        grid-template-columns: 260px 1fr;
        gap: 24px;
        padding: 34px 0 70px;
    }

    .lesson-nav {
        position: sticky;
        top: 18px;
        max-height: 82vh;
        overflow: auto;
        padding: 17px;
        border: 1px solid #dfe8e2;
        border-radius: 15px;
        background: #fff;
    }

    .lesson-nav a {
        display: block;
        padding: 9px;
        border-radius: 8px;
        color: #536079;
        text-decoration: none;
        font-size: 12px;
    }

    .lesson-nav a:hover {
        color: #15803d;
        background: #f0fdf4;
    }

    .progress-shell {
        height: 8px;
        margin: 12px 0;
        border-radius: 20px;
        background: #e7eee9;
    }

    .progress {
        height: 100%;
        width: 0;
        border-radius: 20px;
        background: #16a34a;
        transition: width .3s ease;
    }

    /* =========================================================
       SEARCH
    ========================================================= */

    .spring-search {
        position: sticky;
        top: 12px;
        z-index: 30;
        margin-bottom: 22px;
        padding: 12px;
        border: 1px solid #dfe8e2;
        border-radius: 13px;
        background: rgba(255, 255, 255, .96);
        backdrop-filter: blur(10px);
        box-shadow: 0 8px 25px rgba(15, 23, 42, .06);
    }

    .spring-search input {
        width: 100%;
        box-sizing: border-box;
        padding: 13px 15px;
        border: 1px solid #cbd5e1;
        border-radius: 9px;
        outline: none;
        font-size: 15px;
    }

    .spring-search input:focus {
        border-color: #16a34a;
        box-shadow: 0 0 0 3px rgba(22, 163, 74, .10);
    }

    .spring-search-result {
        display: none;
        margin-top: 8px;
        color: #64748b;
        font-size: 13px;
    }

    /* =========================================================
       SPRING ROADMAP
    ========================================================= */

    .spring-roadmap {
        margin-bottom: 25px;
        padding: 24px;
        border: 1px solid #bbf7d0;
        border-radius: 17px;
        background: linear-gradient(135deg,
                #f0fdf4,
                #ecfdf5);
    }

    .spring-roadmap h2 {
        margin: 0 0 10px;
        color: #14532d;
    }

    .spring-roadmap p {
        color: #536079;
        line-height: 1.7;
    }

    .roadmap-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        margin-top: 18px;
    }

    .roadmap-item {
        padding: 15px;
        border: 1px solid #d1fae5;
        border-radius: 11px;
        background: #fff;
    }

    .roadmap-item strong {
        display: block;
        margin-bottom: 5px;
        color: #166534;
    }

    .roadmap-item span {
        color: #64748b;
        font-size: 13px;
        line-height: 1.5;
    }

    /* =========================================================
       REAL WORLD SPRING EXAMPLES
    ========================================================= */

    .real-world {
        margin-bottom: 25px;
        padding: 24px;
        border: 1px solid #dbe7df;
        border-radius: 17px;
        background: #fff;
    }

    .real-world h2 {
        margin: 0 0 8px;
        color: #172033;
    }

    .real-world>p {
        color: #536079;
        line-height: 1.7;
    }

    .real-world-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 15px;
        margin-top: 18px;
    }

    .real-world-card {
        padding: 17px;
        border: 1px solid #dfe8e2;
        border-radius: 13px;
        background: #f8fafc;
    }

    .real-world-card h3 {
        margin: 0 0 8px;
        color: #166534;
    }

    .real-world-card p {
        margin: 0;
        color: #536079;
        line-height: 1.6;
        font-size: 14px;
    }

    .spring-code-example {
        overflow-x: auto;
        margin-top: 12px;
        padding: 15px;
        border-radius: 9px;
        background: #0f172a;
        color: #bbf7d0;
        font: 13px/1.65 Consolas, Monaco, monospace;
    }

    /* =========================================================
       LESSON
    ========================================================= */

    .lesson {
        margin-bottom: 20px;
        overflow: hidden;
        border: 1px solid #dfe8e2;
        border-radius: 17px;
        background: #fff;
    }

    .lesson[open] {
        border-color: #86efac;
        box-shadow: 0 12px 30px #14532d12;
    }

    .lesson.search-hidden {
        display: none;
    }

    .lesson summary {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 21px;
        cursor: pointer;
        list-style: none;
    }

    .lesson summary::-webkit-details-marker {
        display: none;
    }

    .number {
        display: grid;
        place-items: center;
        width: 42px;
        height: 42px;
        flex: none;
        border-radius: 12px;
        background: #dcfce7;
        color: #15803d;
        font-weight: 850;
    }

    .title {
        font-size: 19px;
        font-weight: 800;
    }

    .body {
        padding: 0 24px 25px;
    }

    .concept {
        padding: 18px;
        border-left: 4px solid #16a34a;
        border-radius: 0 11px 11px 0;
        background: #f0fdf4;
        line-height: 1.7;
    }

    /* =========================================================
       ARCHITECTURE
    ========================================================= */

    .architecture {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        margin: 19px 0;
        padding: 18px;
        border-radius: 13px;
        background: #102a25;
        color: #fff;
    }

    .architecture span {
        padding: 8px 11px;
        border-radius: 8px;
        background: #ffffff12;
    }

    /* =========================================================
       CODE
    ========================================================= */

    .code-head {
        display: flex;
        justify-content: space-between;
        padding: 10px 14px;
        border-radius: 11px 11px 0 0;
        background: #15231f;
        color: #d1fae5;
    }

    .copy {
        border: 0;
        border-radius: 7px;
        padding: 5px 9px;
        background: #ffffff17;
        color: #fff;
        cursor: pointer;
    }

    .code {
        overflow: auto;
        margin: 0;
        padding: 18px;
        border-radius: 0 0 11px 11px;
        background: #081510;
        color: #bbf7d0;
        font: 14px/1.65 Consolas, monospace;
        white-space: pre;
    }

    /* =========================================================
       LESSON SECTIONS
    ========================================================= */

    .lesson-sections {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin: 18px 0;
    }

    .lesson-section {
        padding: 15px;
        border: 1px solid #dbe7ff;
        border-radius: 11px;
        background: #f8fbff;
    }

    .lesson-section h3 {
        margin: 0 0 6px;
        font-size: 15px;
    }

    .lesson-section p {
        margin: 0;
        color: #536079;
        line-height: 1.6;
    }

    /* =========================================================
       CHALLENGE
    ========================================================= */

    .challenge {
        margin: 19px 0;
        padding: 17px;
        border: 1px solid #bae6fd;
        border-radius: 12px;
        background: #f0f9ff;
    }

    /* =========================================================
       QUIZ
    ========================================================= */

    .quiz {
        padding: 19px;
        border: 1px solid #dfe8e2;
        border-radius: 13px;
        background: #f8fafc;
    }

    .options {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }

    .option {
        padding: 12px;
        text-align: left;
        border: 1px solid #d9e2dc;
        border-radius: 9px;
        background: #fff;
        cursor: pointer;
    }

    .option:hover {
        border-color: #86efac;
        background: #f0fdf4;
    }

    .option.correct {
        border-color: #16a34a;
        background: #f0fdf4;
        color: #166534;
    }

    .option.wrong {
        border-color: #dc2626;
        background: #fef2f2;
        color: #991b1b;
    }

    .feedback {
        display: none;
        margin: 12px 0 0;
        padding: 11px;
        border-radius: 8px;
    }

    .feedback.show {
        display: block;
    }

    .feedback.good {
        background: #dcfce7;
        color: #166534;
    }

    .feedback.bad {
        background: #fee2e2;
        color: #991b1b;
    }

    /* =========================================================
       10 LESSON KNOWLEDGE CHECK
    ========================================================= */

    .block-quiz {
        margin: 30px 0;
        padding: 25px;
        border: 1px solid #86efac;
        border-radius: 18px;
        background: linear-gradient(135deg,
                #f0fdf4,
                #ecfdf5);
    }

    .block-quiz-label {
        color: #15803d;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .block-quiz h2 {
        margin: 5px 0 8px;
        color: #14532d;
    }

    .block-quiz p {
        color: #536079;
    }

    .block-question {
        margin-top: 14px;
        padding: 17px;
        border: 1px solid #dbe7df;
        border-radius: 12px;
        background: #fff;
    }

    .block-question strong {
        display: block;
        margin-bottom: 10px;
    }

    .block-option {
        display: block;
        margin: 7px 0;
        padding: 9px 11px;
        border: 1px solid #dbe7df;
        border-radius: 8px;
        cursor: pointer;
    }

    .block-option:hover {
        background: #f0fdf4;
    }

    .block-quiz-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 18px;
    }

    .block-quiz-button {
        padding: 10px 16px;
        border: 0;
        border-radius: 8px;
        background: #15803d;
        color: #fff;
        font-weight: 750;
        cursor: pointer;
    }

    .block-quiz-button.secondary {
        background: #e2e8f0;
        color: #334155;
    }

    .block-quiz-result {
        display: none;
        margin-top: 14px;
        padding: 14px;
        border-radius: 9px;
        background: #fff;
        font-weight: 700;
    }

    /* =========================================================
       LAB
    ========================================================= */

    .lab {
        margin: 20px 0;
        border: 1px solid #bfdbfe;
        border-radius: 14px;
        overflow: hidden;
    }

    .lab-head {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
        padding: 13px 16px;
        background: #172554;
        color: #fff;
    }

    .language-tabs {
        display: flex;
        gap: 7px;
        flex-wrap: wrap;
    }

    .language-tab {
        padding: 7px 10px;
        border: 1px solid #ffffff33;
        border-radius: 7px;
        background: #ffffff12;
        color: #fff;
        cursor: pointer;
    }

    .language-tab.active {
        background: #2563eb;
    }

    .lab-grid {
        display: grid;
        grid-template-columns: 1.15fr .85fr;
    }

    .lab-pane {
        padding: 15px;
        background: #0f172a;
    }

    .lab-code {
        width: 100%;
        min-height: 210px;
        padding: 14px;
        resize: vertical;
        border: 1px solid #334155;
        border-radius: 9px;
        background: #020617;
        color: #bfdbfe;
        font: 13px/1.55 Consolas, monospace;
    }

    .lab-io {
        display: grid;
        gap: 11px;
        padding: 15px;
        background: #f8fafc;
    }

    .lab-io textarea,
    .lab-output {
        width: 100%;
        min-height: 105px;
        padding: 12px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #fff;
        font: 13px/1.55 Consolas, monospace;
        white-space: pre-wrap;
    }

    .lab-run {
        padding: 9px 14px;
        border: 0;
        border-radius: 8px;
        background: #2563eb;
        color: #fff;
        font-weight: 750;
        cursor: pointer;
    }

    .lab-note {
        margin: 0;
        color: #64748b;
        font-size: 12px;
    }

    /* =========================================================
       RESPONSIVE
    ========================================================= */

    @media(max-width:850px) {

        .course {
            grid-template-columns: 1fr;
        }

        .lesson-nav {
            position: static;
            max-height: 260px;
        }

        .options {
            grid-template-columns: 1fr;
        }

        .roadmap-grid,
        .real-world-grid,
        .lesson-sections,
        .lab-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

@endpush


@section('content')

<section class="spring-hero">

    <div class="course-wrap">

        <a href="{{ route('learning.track', $trackSlug) }}">
            ← {{ $track['title'] }}
        </a>

        <h1>{{ $module['title'] }}</h1>

        <p>
            Learn Spring and Spring Boot through practical concepts,
            production-style examples, code, hands-on exercises and
            interview questions.
        </p>

        <div class="flow">

            <span>Understand</span>
            →
            <span>See Example</span>
            →
            <span>Code</span>
            →
            <span>Practice</span>
            →
            <span>Quiz</span>

        </div>

    </div>

</section>


<div class="course-wrap course">

    {{-- =====================================================
         LEFT NAVIGATION
    ====================================================== --}}

    <aside class="lesson-nav">

        <strong>
            Completed:
            <span id="score">0</span>
            /
            {{ count($lessons) }}
        </strong>

        <div class="progress-shell">

            <div
                id="progress"
                class="progress">
            </div>

        </div>


        <input
            type="search"
            id="lessonSearch"
            placeholder="Search Spring lessons..."
            style="
                width:100%;
                box-sizing:border-box;
                padding:10px;
                border:1px solid #dbe5df;
                border-radius:8px;
                margin:10px 0;
            ">


        @foreach($lessons as $lesson)

        <a href="#lesson-{{ $loop->iteration }}">

            {{ $loop->iteration }}.
            {{ $lesson['title'] }}

        </a>

        @endforeach

    </aside>


    {{-- =====================================================
         MAIN
    ====================================================== --}}

    <main>

        @include('learning.partials.visual-overview')


        {{-- =================================================
             SPRING ROADMAP
        ================================================== --}}

        <section class="spring-roadmap">

            <h2>Complete Spring / Spring Boot Roadmap</h2>

            <p>
                The existing lessons remain the learning source.
                This roadmap helps learners understand how the concepts
                connect together when building real applications.
            </p>


            <div class="roadmap-grid">

                <div class="roadmap-item">

                    <strong>1. Spring Core</strong>

                    <span>
                        IoC, Dependency Injection, Beans,
                        ApplicationContext and configuration.
                    </span>

                </div>


                <div class="roadmap-item">

                    <strong>2. Spring Boot</strong>

                    <span>
                        Auto configuration, starters,
                        application configuration and embedded servers.
                    </span>

                </div>


                <div class="roadmap-item">

                    <strong>3. REST APIs</strong>

                    <span>
                        Controllers, request mapping, DTOs,
                        validation and response handling.
                    </span>

                </div>


                <div class="roadmap-item">

                    <strong>4. Database</strong>

                    <span>
                        Spring Data JPA, repositories,
                        entities, relationships and transactions.
                    </span>

                </div>


                <div class="roadmap-item">

                    <strong>5. Security</strong>

                    <span>
                        Authentication, authorization,
                        JWT and protected APIs.
                    </span>

                </div>


                <div class="roadmap-item">

                    <strong>6. Production</strong>

                    <span>
                        Logging, Actuator, exception handling,
                        testing, caching, queues and observability.
                    </span>

                </div>

            </div>

        </section>


        {{-- =================================================
             REAL WORLD EXAMPLES
        ================================================== --}}

        <section class="real-world">

            <h2>Learn Spring with real applications</h2>

            <p>
                Instead of learning Spring concepts in isolation,
                connect them with applications developers actually build.
            </p>


            <div class="real-world-grid">

                <div class="real-world-card">

                    <h3>Banking Application</h3>

                    <p>
                        Customer accounts, transactions,
                        authentication, balance checks and
                        transaction processing.
                    </p>

                    <div class="spring-code-example">Controller
                        ↓
                        Service
                        ↓
                        Repository
                        ↓
                        Database</div>

                </div>


                <div class="real-world-card">

                    <h3>E-commerce Application</h3>

                    <p>
                        Products, customers, carts, orders,
                        payments and inventory.
                    </p>

                    <div class="spring-code-example">@RestController
                        class OrderController {

                        @PostMapping("/orders")
                        public Order createOrder(
                        @RequestBody OrderRequest request
                        ) {
                        return orderService.create(request);
                        }
                        }</div>

                </div>


                <div class="real-world-card">

                    <h3>Employee Management</h3>

                    <p>
                        Employees, departments, managers,
                        salaries and role-based access.
                    </p>

                    <div class="spring-code-example">@Service
                        class EmployeeService {

                        public Employee findById(Long id) {
                        return repository.findById(id)
                        .orElseThrow();
                        }
                        }</div>

                </div>


                <div class="real-world-card">

                    <h3>Travel / Booking System</h3>

                    <p>
                        Flights, hotels, customers, bookings,
                        payments and notifications.
                    </p>

                    <div class="spring-code-example">@Repository
                        interface BookingRepository
                        extends JpaRepository&lt;Booking, Long&gt; {

                        }</div>

                </div>

            </div>

        </section>


        {{-- =================================================
             SEARCH
        ================================================== --}}

        <div class="spring-search">

            <input
                type="search"
                id="springQuestionSearch"
                placeholder="Search Spring: dependency injection, REST, JPA, Kafka, security, microservices..."
                autocomplete="off">

            <div
                id="springSearchResult"
                class="spring-search-result">
            </div>

        </div>


        {{-- =================================================
             EXISTING LESSONS
        ================================================== --}}

        @foreach($lessons as $lesson)

        <details
            class="lesson"
            id="lesson-{{ $loop->iteration }}"
            data-spring-lesson
            data-search-text="{{ strtolower($lesson['title'].' '.$lesson['summary'].' '.$lesson['task']) }}"
            {{ $loop->first ? 'open' : '' }}>

            <summary>

                <span class="number">

                    {{ str_pad(
                            $loop->iteration,
                            2,
                            '0',
                            STR_PAD_LEFT
                        ) }}

                </span>


                <span class="title">

                    {{ $lesson['title'] }}

                </span>

            </summary>


            <div class="body">


                {{-- =====================================
                         CONCEPT
                    ====================================== --}}

                <div class="concept">

                    <h3>Solution and explanation</h3>

                    @include(
                    'learning.partials.answer-blocks',
                    [
                    'answerText' =>
                    $lesson['summary']
                    ]
                    )

                </div>


                {{-- =====================================
                         CONCEPT STRUCTURE
                    ====================================== --}}

                <div class="lesson-sections">

                    <div class="lesson-section">

                        <h3>
                            What you should understand
                        </h3>

                        <p>
                            Understand the concept first,
                            then connect it with the code example
                            below.
                        </p>

                    </div>


                    <div class="lesson-section">

                        <h3>
                            Interview perspective
                        </h3>

                        <p>
                            Be able to explain why this concept
                            is used and where it belongs in a
                            Spring application.
                        </p>

                    </div>

                </div>


                {{-- =====================================
                         EXISTING INTERACTIVE LAB
                    ====================================== --}}

                @include(
                'learning.partials.io-lab',
                ['lesson' => $lesson]
                )


                {{-- =====================================
                         EXISTING RECAP
                    ====================================== --}}

                @include(
                'learning.partials.lesson-recap'
                )


                {{-- =====================================
                         CODE
                    ====================================== --}}

                <div class="code-head">

                    <strong>
                        Run this example
                    </strong>

                    <button
                        class="copy"
                        type="button">

                        Copy code

                    </button>

                </div>


                <pre class="code"><code>{{ $lesson['code'] }}</code></pre>


                {{-- =====================================
                         HANDS-ON
                    ====================================== --}}

                <div class="challenge">

                    <strong>
                        Hands-on challenge
                    </strong>

                    <p>
                        {{ $lesson['task'] }}
                    </p>

                </div>


                {{-- =====================================
                         EXISTING LESSON QUIZ
                    ====================================== --}}

                <section
                    class="quiz"
                    data-answer="{{ $lesson['answer'] }}"
                    data-why="{{ $lesson['why'] }}"
                    data-lesson="{{ $loop->iteration }}">

                    <h3>
                        Quick check
                    </h3>

                    <p>
                        Which statement is correct?
                    </p>


                    <div class="options">

                        @foreach($lesson['options'] as $option)

                        <button
                            class="option"
                            type="button"
                            data-option="{{ $loop->index }}">

                            <b>
                                {{ chr(65 + $loop->index) }}.
                            </b>

                            {{ $option }}

                        </button>

                        @endforeach

                    </div>


                    <p
                        class="feedback"
                        aria-live="polite">
                    </p>

                </section>


            </div>

        </details>


        {{-- =================================================
                 KNOWLEDGE CHECK AFTER EVERY 10 LESSONS
            ================================================== --}}

        @if($loop->iteration % 10 === 0)

        @php

        $blockStart =
        $loop->iteration - 9;

        $blockEnd =
        $loop->iteration;

        $blockLessons =
        array_slice(
        $lessons,
        $blockStart - 1,
        10
        );

        @endphp


        <section
            class="block-quiz"
            data-block-quiz>

            <span class="block-quiz-label">
                Knowledge Check
            </span>


            <h2>
                Spring Quiz:
                Lessons
                {{ $blockStart }}
                –
                {{ $blockEnd }}
            </h2>


            <p>
                Test what you have learned before continuing
                to the next learning block.
            </p>


            @foreach($blockLessons as $quizLessonIndex => $quizLesson)

            <div
                class="block-question"
                data-block-question>

                <strong>

                    {{ $quizLessonIndex + 1 }}.
                    {{ $quizLesson['title'] }}

                </strong>


                <p>
                    Which statement is correct?
                </p>


                <div>

                    @foreach($quizLesson['options'] as $optionIndex => $option)

                    <label class="block-option">

                        <input
                            type="radio"
                            name="spring_block_{{ $blockStart }}_{{ $quizLessonIndex }}"
                            value="{{ $optionIndex }}">

                        {{ chr(65 + $optionIndex) }}.
                        {{ $option }}

                    </label>

                    @endforeach

                </div>


                <input
                    type="hidden"
                    value="{{ $quizLesson['answer'] }}"
                    data-correct-answer>

            </div>

            @endforeach


            <div class="block-quiz-actions">

                <button
                    type="button"
                    class="block-quiz-button"
                    data-check-block-quiz>

                    Check Quiz

                </button>


                <button
                    type="button"
                    class="block-quiz-button secondary"
                    data-reset-block-quiz>

                    Reset

                </button>

            </div>


            <div
                class="block-quiz-result"
                data-block-result></div>

        </section>

        @endif

        @endforeach

    </main>

</div>


@push('scripts')

<script>
    (() => {

        /* =========================================================
           PROGRESS
        ========================================================= */

        const key =
            'ascendia_spring_{{ $moduleSlug }}';

        const done =
            new Set(
                JSON.parse(
                    localStorage.getItem(key) || '[]'
                )
            );

        const score =
            document.querySelector('#score');

        const bar =
            document.querySelector('#progress');

        const total = {
            {
                count($lessons)
            }
        };


        const paint = () => {

            score.textContent =
                done.size;

            bar.style.width =
                (
                    total > 0 ?
                    done.size / total * 100 :
                    0
                ) + '%';

        };


        paint();


        /* =========================================================
           EXISTING LESSON QUIZZES
        ========================================================= */

        document
            .querySelectorAll('.quiz')
            .forEach(quiz => {

                const feedback =
                    quiz.querySelector('.feedback');

                const answer =
                    Number(
                        quiz.dataset.answer
                    );


                quiz
                    .querySelectorAll('.option')
                    .forEach(button => {

                        button.addEventListener(
                            'click',
                            () => {

                                quiz
                                    .querySelectorAll('.option')
                                    .forEach(option => {

                                        option.disabled = true;

                                        if (
                                            Number(
                                                option.dataset.option
                                            ) === answer
                                        ) {

                                            option.classList.add(
                                                'correct'
                                            );

                                        }

                                    });


                                const correct =
                                    Number(
                                        button.dataset.option
                                    ) === answer;


                                if (!correct) {

                                    button.classList.add(
                                        'wrong'
                                    );

                                }


                                feedback.className =
                                    'feedback show ' +
                                    (
                                        correct ?
                                        'good' :
                                        'bad'
                                    );


                                feedback.textContent =
                                    (
                                        correct ?
                                        'Correct! ' :
                                        'Not quite. '
                                    ) +
                                    quiz.dataset.why;


                                if (correct) {

                                    done.add(
                                        Number(
                                            quiz.dataset.lesson
                                        )
                                    );


                                    localStorage.setItem(
                                        key,
                                        JSON.stringify(
                                            [...done]
                                        )
                                    );


                                    paint();

                                }

                            }
                        );

                    });

            });


        /* =========================================================
           COPY CODE
        ========================================================= */

        document
            .querySelectorAll('.copy')
            .forEach(button => {

                button.addEventListener(
                    'click',
                    async () => {

                        const code =
                            button
                            .closest('.body')
                            .querySelector('code')
                            .textContent;


                        try {

                            await navigator.clipboard.writeText(
                                code
                            );

                            button.textContent =
                                'Copied!';

                            setTimeout(
                                () => {
                                    button.textContent =
                                        'Copy code';
                                },
                                1200
                            );

                        } catch (error) {

                            button.textContent =
                                'Copy failed';

                        }

                    }
                );

            });


        /* =========================================================
           LESSON SEARCH
        ========================================================= */

        const searchInput =
            document.querySelector(
                '#springQuestionSearch'
            );

        const searchResult =
            document.querySelector(
                '#springSearchResult'
            );

        const lessons =
            document.querySelectorAll(
                '[data-spring-lesson]'
            );


        if (searchInput) {

            searchInput.addEventListener(
                'input',
                function() {

                    const search =
                        this.value
                        .trim()
                        .toLowerCase();


                    let found = 0;


                    lessons.forEach(lesson => {

                        const text =
                            lesson.dataset.searchText ||
                            '';


                        const matches = !search ||
                            text.includes(search);


                        lesson.classList.toggle(
                            'search-hidden',
                            !matches
                        );


                        if (matches) {
                            found++;
                        }

                    });


                    if (search) {

                        searchResult.style.display =
                            'block';

                        searchResult.textContent =
                            found +
                            ' Spring lesson(s) found.';

                    } else {

                        searchResult.style.display =
                            'none';

                    }

                }
            );

        }


        /* =========================================================
           LEFT NAV SEARCH
        ========================================================= */

        const lessonNavSearch =
            document.querySelector(
                '#lessonSearch'
            );


        if (lessonNavSearch) {

            lessonNavSearch.addEventListener(
                'input',
                function() {

                    const value =
                        this.value
                        .trim()
                        .toLowerCase();


                    document
                        .querySelectorAll(
                            '.lesson-nav a'
                        )
                        .forEach(link => {

                            const text =
                                link.textContent
                                .toLowerCase();


                            link.style.display = !value ||
                                text.includes(value) ?
                                'block' :
                                'none';

                        });

                }
            );

        }


        /* =========================================================
           10-LESSON QUIZZES
        ========================================================= */

        document
            .querySelectorAll('[data-block-quiz]')
            .forEach(quiz => {

                const check =
                    quiz.querySelector(
                        '[data-check-block-quiz]'
                    );

                const reset =
                    quiz.querySelector(
                        '[data-reset-block-quiz]'
                    );

                const result =
                    quiz.querySelector(
                        '[data-block-result]'
                    );


                if (check) {

                    check.addEventListener(
                        'click',
                        () => {

                            const questions =
                                quiz.querySelectorAll(
                                    '[data-block-question]'
                                );


                            let score = 0;

                            let answered = 0;


                            questions.forEach(question => {

                                const selected =
                                    question.querySelector(
                                        'input[type="radio"]:checked'
                                    );

                                const correct =
                                    Number(
                                        question
                                        .querySelector(
                                            '[data-correct-answer]'
                                        )
                                        .value
                                    );


                                if (!selected) {
                                    return;
                                }


                                answered++;


                                if (
                                    Number(
                                        selected.value
                                    ) === correct
                                ) {

                                    score++;

                                }

                            });


                            result.style.display =
                                'block';


                            if (!answered) {

                                result.textContent =
                                    'Please answer the quiz before checking it.';

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
                                    ' Excellent! You have completed this Spring learning block.';

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
                                    ' Review this block before moving ahead.';

                            }

                        }
                    );

                }


                if (reset) {

                    reset.addEventListener(
                        'click',
                        () => {

                            quiz
                                .querySelectorAll(
                                    'input[type="radio"]'
                                )
                                .forEach(input => {

                                    input.checked =
                                        false;

                                });


                            result.style.display =
                                'none';

                            result.textContent =
                                '';

                        }
                    );

                }

            });

    })();
</script>

@endpush

@endsection

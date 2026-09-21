@extends('layouts.app')

@section('title', 'Data Structures & Algorithms — '.config('platform.name'))

@push('styles')

<style>
    /* =========================================================
       DSA HERO
    ========================================================= */

    .dsa-hero {
        padding: 52px 20px;
        color: #fff;
        background:
            radial-gradient(circle at 82% 18%,
                #60a5fa44,
                transparent 30%),
            linear-gradient(125deg,
                #0f1f3b,
                #1d4ed8);
    }

    .dsa-wrap {
        width: min(1180px, calc(100% - 32px));
        margin: auto;
    }

    .dsa-hero a {
        color: #bfdbfe;
    }

    .dsa-hero h1 {
        margin: 18px 0 8px;
        font-size: clamp(34px, 5vw, 54px);
    }

    .dsa-hero p {
        max-width: 900px;
        line-height: 1.75;
        color: #dbeafe;
    }

    .dsa-flow {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
        margin-top: 22px;
    }

    .dsa-flow span {
        padding: 8px 12px;
        border: 1px solid #ffffff2b;
        border-radius: 9px;
        background: #ffffff12;
    }


    /* =========================================================
       MAIN LAYOUT
    ========================================================= */

    .dsa-course {
        display: grid;
        grid-template-columns: 260px 1fr;
        gap: 24px;
        padding: 34px 0 70px;
    }


    /* =========================================================
       SIDE NAV
    ========================================================= */

    .dsa-nav {
        position: sticky;
        top: 18px;
        max-height: 82vh;
        overflow: auto;
        padding: 17px;
        border: 1px solid #dbe4f0;
        border-radius: 15px;
        background: #fff;
    }

    .dsa-nav-title {
        font-size: 17px;
        font-weight: 800;
        color: #172033;
    }

    .dsa-progress-shell {
        height: 8px;
        margin: 12px 0 15px;
        border-radius: 20px;
        background: #e5e7eb;
    }

    .dsa-progress {
        width: 0;
        height: 100%;
        border-radius: 20px;
        background: #2563eb;
        transition: width .3s ease;
    }

    .dsa-nav-search {
        width: 100%;
        box-sizing: border-box;
        margin-bottom: 10px;
        padding: 10px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        outline: none;
    }

    .dsa-nav-search:focus {
        border-color: #2563eb;
    }

    .dsa-nav a {
        display: block;
        padding: 9px;
        border-radius: 8px;
        color: #536079;
        text-decoration: none;
        font-size: 12px;
    }

    .dsa-nav a:hover {
        color: #1d4ed8;
        background: #eff6ff;
    }


    /* =========================================================
       SEARCH
    ========================================================= */

    .dsa-search {
        position: sticky;
        top: 12px;
        z-index: 30;
        margin-bottom: 22px;
        padding: 12px;
        border: 1px solid #dbe4f0;
        border-radius: 13px;
        background: rgba(255, 255, 255, .96);
        backdrop-filter: blur(10px);
        box-shadow: 0 8px 25px rgba(15, 23, 42, .06);
    }

    .dsa-search input {
        width: 100%;
        box-sizing: border-box;
        padding: 13px 15px;
        border: 1px solid #cbd5e1;
        border-radius: 9px;
        outline: none;
        font-size: 15px;
    }

    .dsa-search input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .10);
    }

    .dsa-search-result {
        display: none;
        margin-top: 8px;
        color: #64748b;
        font-size: 13px;
    }


    /* =========================================================
       ROADMAP
    ========================================================= */

    .dsa-roadmap {
        margin-bottom: 25px;
        padding: 25px;
        border: 1px solid #bfdbfe;
        border-radius: 18px;
        background: linear-gradient(135deg,
                #eff6ff,
                #f8fafc);
    }

    .dsa-roadmap h2 {
        margin: 0 0 8px;
        color: #172554;
    }

    .dsa-roadmap>p {
        color: #536079;
        line-height: 1.7;
    }

    .dsa-roadmap-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        margin-top: 18px;
    }

    .dsa-roadmap-card {
        padding: 15px;
        border: 1px solid #dbeafe;
        border-radius: 11px;
        background: #fff;
    }

    .dsa-roadmap-card strong {
        display: block;
        margin-bottom: 6px;
        color: #1d4ed8;
    }

    .dsa-roadmap-card span {
        color: #64748b;
        font-size: 13px;
        line-height: 1.55;
    }


    /* =========================================================
       REAL WORLD EXAMPLES
    ========================================================= */

    .dsa-real-world {
        margin-bottom: 25px;
        padding: 25px;
        border: 1px solid #dbe4f0;
        border-radius: 18px;
        background: #fff;
    }

    .dsa-real-world h2 {
        margin: 0 0 8px;
        color: #172033;
    }

    .dsa-real-world>p {
        color: #536079;
        line-height: 1.7;
    }

    .dsa-example-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 15px;
        margin-top: 18px;
    }

    .dsa-example {
        padding: 17px;
        border: 1px solid #dbe4f0;
        border-radius: 13px;
        background: #f8fafc;
    }

    .dsa-example h3 {
        margin: 0 0 8px;
        color: #1e3a8a;
    }

    .dsa-example p {
        margin: 0;
        color: #536079;
        line-height: 1.65;
        font-size: 14px;
    }

    .dsa-example-code {
        overflow-x: auto;
        margin-top: 12px;
        padding: 15px;
        border-radius: 9px;
        background: #0f172a;
        color: #bfdbfe;
        font: 13px/1.65 Consolas, Monaco, monospace;
    }


    /* =========================================================
       LESSON
    ========================================================= */

    .dsa-lesson {
        margin-bottom: 20px;
        overflow: hidden;
        border: 1px solid #dbe4f0;
        border-radius: 18px;
        background: #fff;
    }

    .dsa-lesson[open] {
        border-color: #93c5fd;
        box-shadow: 0 12px 30px rgba(29, 78, 216, .08);
    }

    .dsa-lesson.search-hidden {
        display: none;
    }

    .dsa-lesson summary {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 21px;
        cursor: pointer;
        list-style: none;
    }

    .dsa-lesson summary::-webkit-details-marker {
        display: none;
    }

    .dsa-number {
        display: grid;
        place-items: center;
        width: 42px;
        height: 42px;
        flex: none;
        border-radius: 12px;
        background: #dbeafe;
        color: #1d4ed8;
        font-weight: 850;
    }

    .dsa-title {
        font-size: 19px;
        font-weight: 800;
        color: #172033;
    }

    .dsa-body {
        padding: 0 24px 25px;
    }


    /* =========================================================
       SUMMARY
    ========================================================= */

    .dsa-concept {
        padding: 19px;
        border-left: 4px solid #2563eb;
        border-radius: 0 11px 11px 0;
        background: #eff6ff;
        line-height: 1.75;
    }


    /* =========================================================
       CONCEPT DETAILS
    ========================================================= */

    .dsa-section-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin: 18px 0;
    }

    .dsa-section {
        padding: 16px;
        border: 1px solid #dbeafe;
        border-radius: 11px;
        background: #f8fbff;
    }

    .dsa-section h3 {
        margin: 0 0 7px;
        font-size: 15px;
        color: #1e3a8a;
    }

    .dsa-section p {
        margin: 0;
        color: #536079;
        line-height: 1.6;
    }


    /* =========================================================
       CODE
    ========================================================= */

    .dsa-code-title {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        align-items: center;
        padding: 11px 15px;
        border-radius: 12px 12px 0 0;
        background: #0f172a;
        color: #fff;
    }

    .dsa-copy {
        padding: 5px 10px;
        border: 0;
        border-radius: 7px;
        background: #ffffff17;
        color: #fff;
        cursor: pointer;
    }

    .dsa-code {
        overflow: auto;
        margin: 0;
        padding: 20px;
        border-radius: 0 0 12px 12px;
        background: #020617;
        color: #bfdbfe;
        font: 14px/1.7 Consolas, monospace;
        white-space: pre;
    }


    /* =========================================================
       QUESTION SOLUTION
    ========================================================= */

    .dsa-solution {
        margin: 18px 0;
        padding: 19px;
        border: 1px solid #bfdbfe;
        border-left: 4px solid #2563eb;
        border-radius: 0 13px 13px 0;
        background: #f8fbff;
    }

    .dsa-solution h3 {
        margin: 0 0 9px;
        color: #1e3a8a;
    }

    .dsa-solution .correct-answer {
        margin-bottom: 12px;
        padding: 11px 13px;
        border-radius: 8px;
        background: #dbeafe;
        color: #1e3a8a;
        font-weight: 700;
    }

    .dsa-solution .solution-text {
        color: #334155;
        line-height: 1.75;
    }

    .dsa-solution .solution-note {
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #dbeafe;
        color: #64748b;
        line-height: 1.65;
        font-size: 14px;
    }

    .dsa-block-solution {
        display: none;
        margin-top: 12px;
        padding: 13px;
        border-radius: 9px;
        background: #f0fdf4;
        border-left: 3px solid #16a34a;
        color: #334155;
        line-height: 1.65;
    }

    .dsa-block-solution.show {
        display: block;
    }


    /* =========================================================
       COMPLEXITY
    ========================================================= */

    .complexity-box {
        margin: 20px 0;
        padding: 18px;
        border: 1px solid #bfdbfe;
        border-radius: 13px;
        background: #f8fbff;
    }

    .complexity-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
        margin-top: 12px;
    }

    .complexity-item {
        padding: 12px;
        text-align: center;
        border-radius: 9px;
        background: #fff;
        border: 1px solid #dbeafe;
    }

    .complexity-item strong {
        display: block;
        color: #1d4ed8;
        margin-bottom: 4px;
    }

    .complexity-item span {
        color: #475569;
        font-size: 13px;
    }


    /* =========================================================
       CHALLENGE
    ========================================================= */

    .dsa-challenge {
        margin: 20px 0;
        padding: 18px;
        border: 1px solid #93c5fd;
        border-radius: 13px;
        background: #eff6ff;
    }

    .dsa-challenge h3 {
        margin-top: 0;
        color: #1e3a8a;
    }

    .dsa-challenge p {
        color: #475569;
        line-height: 1.7;
    }


    /* =========================================================
       QUIZ
    ========================================================= */

    .dsa-quiz {
        padding: 20px;
        border: 1px solid #dbe4f0;
        border-radius: 14px;
        background: #f8fafc;
    }

    .dsa-quiz-options {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }

    .dsa-answer {
        padding: 13px;
        text-align: left;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        background: #fff;
        cursor: pointer;
    }

    .dsa-answer:hover {
        border-color: #93c5fd;
        background: #eff6ff;
    }

    .dsa-answer.correct {
        border-color: #16a34a;
        background: #dcfce7;
    }

    .dsa-answer.wrong {
        border-color: #dc2626;
        background: #fee2e2;
    }

    .dsa-feedback {
        display: none;
        padding: 12px;
        margin-top: 12px;
        border-radius: 9px;
    }

    .dsa-feedback.show {
        display: block;
        background: #eff6ff;
    }


    /* =========================================================
       10 LESSON QUIZ
    ========================================================= */

    .dsa-block-quiz {
        margin: 30px 0;
        padding: 25px;
        border: 1px solid #93c5fd;
        border-radius: 18px;
        background: linear-gradient(135deg,
                #eff6ff,
                #f8fafc);
    }

    .dsa-block-label {
        color: #1d4ed8;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .dsa-block-quiz h2 {
        margin: 5px 0 8px;
        color: #172554;
    }

    .dsa-block-quiz>p {
        color: #536079;
    }

    .dsa-block-question {
        margin-top: 14px;
        padding: 17px;
        border: 1px solid #dbe4f0;
        border-radius: 12px;
        background: #fff;
    }

    .dsa-block-question strong {
        display: block;
        margin-bottom: 10px;
    }

    .dsa-block-option {
        display: block;
        margin: 7px 0;
        padding: 10px 12px;
        border: 1px solid #dbe4f0;
        border-radius: 8px;
        cursor: pointer;
    }

    .dsa-block-option:hover {
        background: #eff6ff;
    }

    .dsa-block-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 18px;
    }

    .dsa-block-button {
        padding: 10px 16px;
        border: 0;
        border-radius: 8px;
        background: #2563eb;
        color: #fff;
        font-weight: 750;
        cursor: pointer;
    }

    .dsa-block-button.secondary {
        background: #e2e8f0;
        color: #334155;
    }

    .dsa-block-result {
        display: none;
        margin-top: 14px;
        padding: 14px;
        border-radius: 9px;
        background: #fff;
        font-weight: 700;
    }


    /* =========================================================
       RESPONSIVE
    ========================================================= */

    @media(max-width:850px) {

        .dsa-course {
            grid-template-columns: 1fr;
        }

        .dsa-nav {
            position: static;
            max-height: 280px;
        }

        .dsa-roadmap-grid,
        .dsa-example-grid,
        .dsa-section-grid {
            grid-template-columns: 1fr;
        }

        .complexity-grid {
            grid-template-columns: 1fr;
        }

        .dsa-quiz-options {
            grid-template-columns: 1fr;
        }
    }
</style>

@endpush


@section('content')

<section class="dsa-hero">

    <div class="dsa-wrap">

        <a href="{{ route('learning.track', 'dsa') }}">
            ← Data Structures & Algorithms
        </a>

        <h1>
            Data Structures & Algorithms
        </h1>

        <p>
            Learn DSA from programming fundamentals through arrays,
            strings, linked lists, stacks, queues, trees, graphs,
            recursion, sorting, searching, greedy algorithms,
            dynamic programming and interview problems.
        </p>

        <div class="dsa-flow">

            <span>Understand</span>
            →
            <span>Visualize</span>
            →
            <span>Code</span>
            →
            <span>Analyze</span>
            →
            <span>Practice</span>
            →
            <span>Quiz</span>

        </div>

    </div>

</section>


<div class="dsa-wrap dsa-course">


    {{-- =====================================================
         LEFT NAVIGATION
    ====================================================== --}}

    <aside class="dsa-nav">

        <div class="dsa-nav-title">
            DSA Learning Path
        </div>

        <div style="margin-top:8px;color:#64748b;font-size:13px;">

            Completed:
            <strong id="dsaScore">0</strong>
            /
            {{ count($lessons) }}

        </div>


        <div class="dsa-progress-shell">

            <div
                id="dsaProgress"
                class="dsa-progress">
            </div>

        </div>


        <input
            type="search"
            id="dsaNavSearch"
            class="dsa-nav-search"
            placeholder="Search topic...">


        @foreach($lessons as $lesson)

        <a
            href="#dsa-lesson-{{ $loop->iteration }}"
            data-dsa-nav-link>

            {{ $loop->iteration }}.
            {{ $lesson['title'] }}

        </a>

        @endforeach

    </aside>


    {{-- =====================================================
         MAIN CONTENT
    ====================================================== --}}

    <main>


        @include('learning.partials.visual-overview')


        {{-- =================================================
             SEARCH
        ================================================== --}}

        <div class="dsa-search">

            <input
                type="search"
                id="dsaSearch"
                placeholder="Search DSA lessons... array, recursion, sorting, tree, graph, binary search..."
                autocomplete="off">

            <div
                id="dsaSearchResult"
                class="dsa-search-result">
            </div>

        </div>


        {{-- =================================================
             DSA ROADMAP
        ================================================== --}}

        <section class="dsa-roadmap">

            <h2>
                Complete DSA Learning Roadmap
            </h2>

            <p>
                Learn each data structure and algorithm by understanding
                the problem it solves, seeing a practical example,
                writing code and analysing its complexity.
            </p>


            <div class="dsa-roadmap-grid">

                <div class="dsa-roadmap-card">

                    <strong>
                        Programming Fundamentals
                    </strong>

                    <span>
                        Input/output, variables, conditions,
                        loops, functions and basic problem solving.
                    </span>

                </div>


                <div class="dsa-roadmap-card">

                    <strong>
                        Arrays & Strings
                    </strong>

                    <span>
                        Traversal, insertion, deletion,
                        prefix sums, two pointers and sliding window.
                    </span>

                </div>


                <div class="dsa-roadmap-card">

                    <strong>
                        Linked Lists
                    </strong>

                    <span>
                        Singly linked lists, doubly linked lists,
                        reversal and cycle detection.
                    </span>

                </div>


                <div class="dsa-roadmap-card">

                    <strong>
                        Stack & Queue
                    </strong>

                    <span>
                        LIFO, FIFO, monotonic stack,
                        deque and practical applications.
                    </span>

                </div>


                <div class="dsa-roadmap-card">

                    <strong>
                        Searching & Sorting
                    </strong>

                    <span>
                        Linear search, binary search,
                        merge sort, quick sort and complexity.
                    </span>

                </div>


                <div class="dsa-roadmap-card">

                    <strong>
                        Hashing
                    </strong>

                    <span>
                        Hash maps, hash sets, frequency counting
                        and lookup optimisation.
                    </span>

                </div>


                <div class="dsa-roadmap-card">

                    <strong>
                        Recursion & Backtracking
                    </strong>

                    <span>
                        Recursive thinking, base cases,
                        permutations, combinations and decision trees.
                    </span>

                </div>


                <div class="dsa-roadmap-card">

                    <strong>
                        Trees & Heaps
                    </strong>

                    <span>
                        Binary trees, BST, traversal,
                        heaps and priority queues.
                    </span>

                </div>


                <div class="dsa-roadmap-card">

                    <strong>
                        Graphs
                    </strong>

                    <span>
                        BFS, DFS, shortest paths,
                        connected components and graph problems.
                    </span>

                </div>


                <div class="dsa-roadmap-card">

                    <strong>
                        Greedy Algorithms
                    </strong>

                    <span>
                        Local decisions, scheduling,
                        intervals and optimisation patterns.
                    </span>

                </div>


                <div class="dsa-roadmap-card">

                    <strong>
                        Dynamic Programming
                    </strong>

                    <span>
                        States, transitions, memoization,
                        tabulation and optimisation.
                    </span>

                </div>


                <div class="dsa-roadmap-card">

                    <strong>
                        Interview Problems
                    </strong>

                    <span>
                        Pattern-based problem solving and
                        progressively harder interview questions.
                    </span>

                </div>

            </div>

        </section>


        {{-- =================================================
             REAL WORLD EXAMPLES
        ================================================== --}}

        <section class="dsa-real-world">

            <h2>
                Learn DSA with real-world problems
            </h2>

            <p>
                The goal is not to memorise algorithms. Learn when
                a particular data structure or algorithm is useful.
            </p>


            <div class="dsa-example-grid">

                <div class="dsa-example">

                    <h3>
                        Student Marks
                    </h3>

                    <p>
                        Arrays are useful when a collection of values
                        needs indexed access.
                    </p>

                    <div class="dsa-example-code">int[] marks = {
                        85, 92, 76, 89, 95
                        };

                        for (int mark : marks) {
                        System.out.println(mark);
                        }</div>

                </div>


                <div class="dsa-example">

                    <h3>
                        Browser Back Button
                    </h3>

                    <p>
                        A stack naturally represents last-in-first-out
                        behaviour.
                    </p>

                    <div class="dsa-example-code">Stack&lt;String&gt; history =
                        new Stack&lt;&gt;();

                        history.push("/home");
                        history.push("/jobs");
                        history.push("/profile");

                        String previous =
                        history.pop();</div>

                </div>


                <div class="dsa-example">

                    <h3>
                        Customer Queue
                    </h3>

                    <p>
                        A queue is useful when customers should be
                        processed in arrival order.
                    </p>

                    <div class="dsa-example-code">Queue&lt;String&gt; queue =
                        new LinkedList&lt;&gt;();

                        queue.add("Customer 1");
                        queue.add("Customer 2");

                        String next =
                        queue.poll();</div>

                </div>


                <div class="dsa-example">

                    <h3>
                        Social Network
                    </h3>

                    <p>
                        Graphs can represent people and relationships
                        between them.
                    </p>

                    <div class="dsa-example-code">Person A
                        |
                        +---- Person B
                        |
                        +---- Person C
                        |
                        +---- Person D</div>

                </div>


                <div class="dsa-example">

                    <h3>
                        Search Engine
                    </h3>

                    <p>
                        Searching and indexing algorithms help retrieve
                        information efficiently.
                    </p>

                    <div class="dsa-example-code">Input
                        ↓
                        Search structure
                        ↓
                        Compare / lookup
                        ↓
                        Result</div>

                </div>


                <div class="dsa-example">

                    <h3>
                        Delivery Route
                    </h3>

                    <p>
                        Graph algorithms can help model routes between
                        locations.
                    </p>

                    <div class="dsa-example-code">City A
                        | \
                        | \
                        City B--City C
                        |
                        |
                        City D</div>

                </div>

            </div>

        </section>


        {{-- =================================================
             EXISTING LESSONS
        ================================================== --}}

        @foreach($lessons as $lesson)

        <details
            class="dsa-lesson"
            id="dsa-lesson-{{ $loop->iteration }}"
            data-dsa-lesson
            data-search-text="{{ strtolower(
                    $lesson['title'].' '.
                    $lesson['summary'].' '.
                    $lesson['task']
                ) }}"
            {{ $loop->first ? 'open' : '' }}>

            <summary>

                <span class="dsa-number">

                    {{ str_pad(
                            $loop->iteration,
                            2,
                            '0',
                            STR_PAD_LEFT
                        ) }}

                </span>


                <span class="dsa-title">

                    {{ $lesson['title'] }}

                </span>

            </summary>


            <div class="dsa-body">


                {{-- =====================================
                         CONCEPT
                    ====================================== --}}

                <div class="dsa-concept">

                    {!! nl2br(e($lesson['summary'])) !!}

                </div>


                {{-- =====================================
                         LEARNING SECTIONS
                    ====================================== --}}

                @if(!empty($lesson['sections']))

                <div class="dsa-section-grid">

                    @foreach($lesson['sections'] as $section)

                    <article class="dsa-section">

                        <h3>

                            {{ $loop->iteration }}.
                            {{ $section['title'] }}

                        </h3>

                        <p>
                            {{ $section['text'] }}
                        </p>

                        @if(!empty($section['code']))

                        <pre
                            class="dsa-code"
                            style="margin-top:12px;"><code>{{ $section['code'] }}</code></pre>

                        @endif

                    </article>

                    @endforeach

                </div>

                @endif


                {{-- =====================================
                         COMPLEXITY
                    ====================================== --}}

                <div class="complexity-box">

                    <strong>
                        Algorithm analysis
                    </strong>

                    <p style="color:#64748b;line-height:1.6;">

                        When studying this topic, always ask:
                        how much time does it take, how much memory
                        does it use and how does performance change
                        as input size grows?

                    </p>


                    <div class="complexity-grid">

                        <div class="complexity-item">

                            <strong>
                                Time
                            </strong>

                            <span>
                                Input growth
                            </span>

                        </div>


                        <div class="complexity-item">

                            <strong>
                                Space
                            </strong>

                            <span>
                                Extra memory
                            </span>

                        </div>


                        <div class="complexity-item">

                            <strong>
                                Trade-off
                            </strong>

                            <span>
                                Speed vs memory
                            </span>

                        </div>

                    </div>

                </div>


                {{-- =====================================
                         EXISTING VISUAL / RECAP
                    ====================================== --}}

                @include(
                'learning.partials.lesson-recap'
                )


                {{-- =====================================
                         CODE
                    ====================================== --}}

                @if(!empty($lesson['code']))

                <div class="dsa-code-title">

                    <strong>
                        Run / study this example
                    </strong>

                    <button
                        class="dsa-copy"
                        type="button">
                        Copy code
                    </button>

                </div>


                <pre class="dsa-code"><code>{{ $lesson['code'] }}</code></pre>

                @endif


                {{-- =====================================
                         CHALLENGE
                    ====================================== --}}

                <section class="dsa-challenge">

                    <h3>
                        Hands-on challenge
                    </h3>

                    <p>
                        {{ $lesson['task'] }}
                    </p>

                </section>


                {{-- =====================================
                         EXISTING QUIZ
                    ====================================== --}}

                <section
                    class="dsa-quiz"
                    data-answer="{{ $lesson['answer'] }}"
                    data-why="{{ $lesson['why'] }}"
                    data-lesson="{{ $loop->iteration }}">

                    <h3>
                        Quick quiz
                    </h3>

                    <p>
                        Which statement is correct?
                    </p>


                    <div class="dsa-quiz-options">

                        @foreach($lesson['options'] as $option)

                        <button
                            type="button"
                            class="dsa-answer"
                            data-option="{{ $loop->index }}">

                            <strong>
                                {{ chr(65 + $loop->index) }}.
                            </strong>

                            {{ $option }}

                        </button>

                        @endforeach

                    </div>


                    <p
                        class="dsa-feedback"
                        aria-live="polite"></p>

                </section>


                {{-- =====================================
                         SOLUTION / EXPLANATION
                    ====================================== --}}

                <section class="dsa-solution">

                    <h3>
                        Solution & Explanation
                    </h3>

                    <div class="correct-answer">

                        Correct answer:
                        {{ chr(65 + (int) $lesson['answer']) }}.
                        {{ $lesson['options'][(int) $lesson['answer']] ?? 'See the explanation below.' }}

                    </div>

                    <div class="solution-text">

                        {!! nl2br(e($lesson['why'] ?? '')) !!}

                    </div>

                    <div class="solution-note">

                        <strong>Interview approach:</strong>
                        Explain the concept, give a small example, mention an important
                        edge case or trade-off, and discuss time and space complexity
                        when applicable.

                    </div>

                </section>


            </div>

        </details>


        {{-- =================================================
                 QUIZ AFTER EVERY 10 LESSONS
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
            class="dsa-block-quiz"
            data-dsa-block-quiz>

            <span class="dsa-block-label">
                DSA Knowledge Check
            </span>


            <h2>

                Quiz:
                Lessons
                {{ $blockStart }}
                –
                {{ $blockEnd }}

            </h2>


            <p>
                Test yourself on the concepts you have just
                completed before moving to the next DSA block.
            </p>


            @foreach($blockLessons as $quizLessonIndex => $quizLesson)

            <div
                class="dsa-block-question"
                data-dsa-block-question>

                <strong>

                    {{ $quizLessonIndex + 1 }}.
                    {{ $quizLesson['title'] }}

                </strong>


                <p>
                    Which statement is correct?
                </p>


                @foreach($quizLesson['options'] as $optionIndex => $option)

                <label class="dsa-block-option">

                    <input
                        type="radio"
                        name="dsa_block_{{ $blockStart }}_{{ $quizLessonIndex }}"
                        value="{{ $optionIndex }}">

                    {{ chr(65 + $optionIndex) }}.
                    {{ $option }}

                </label>

                @endforeach


                <input
                    type="hidden"
                    value="{{ $quizLesson['answer'] }}"
                    data-dsa-correct-answer>

                <div
                    class="dsa-block-solution"
                    data-dsa-block-solution>

                    <strong>Solution:</strong>

                    <div style="margin-top:6px;">

                        <strong>Correct answer:</strong>
                        {{ chr(65 + (int) $quizLesson['answer']) }}.
                        {{ $quizLesson['options'][(int) $quizLesson['answer']] ?? '' }}

                    </div>

                    @if(!empty($quizLesson['why']))

                    <div style="margin-top:8px;">
                        {!! nl2br(e($quizLesson['why'])) !!}
                    </div>

                    @endif

                </div>

            </div>

            @endforeach


            <div class="dsa-block-actions">

                <button
                    type="button"
                    class="dsa-block-button"
                    data-check-dsa-block>

                    Check Quiz

                </button>


                <button
                    type="button"
                    class="dsa-block-button secondary"
                    data-reset-dsa-block>

                    Reset

                </button>

            </div>


            <div
                class="dsa-block-result"
                data-dsa-block-result></div>

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

        const storageKey =
            'ascendia_dsa_learning';


        const completed =
            new Set(
                JSON.parse(
                    localStorage.getItem(storageKey) || '[]'
                )
            );


        const scoreElement =
            document.querySelector('#dsaScore');


        const progressElement =
            document.querySelector('#dsaProgress');


        const total = {
            {
                count($lessons)
            }
        };


        const paintProgress = () => {

            scoreElement.textContent =
                completed.size;


            progressElement.style.width =
                (
                    total > 0 ?
                    completed.size / total * 100 :
                    0
                ) + '%';

        };


        paintProgress();


        /* =========================================================
           INDIVIDUAL LESSON QUIZZES
        ========================================================= */

        document
            .querySelectorAll('.dsa-quiz')
            .forEach(quiz => {

                const correct =
                    Number(
                        quiz.dataset.answer
                    );


                const feedback =
                    quiz.querySelector(
                        '.dsa-feedback'
                    );


                quiz
                    .querySelectorAll('.dsa-answer')
                    .forEach(button => {

                        button.addEventListener(
                            'click',
                            () => {

                                quiz
                                    .querySelectorAll(
                                        '.dsa-answer'
                                    )
                                    .forEach(option => {

                                        option.disabled =
                                            true;


                                        if (
                                            Number(
                                                option.dataset.option
                                            ) === correct
                                        ) {

                                            option.classList.add(
                                                'correct'
                                            );

                                        }

                                    });


                                const isCorrect =
                                    Number(
                                        button.dataset.option
                                    ) === correct;


                                if (!isCorrect) {

                                    button.classList.add(
                                        'wrong'
                                    );

                                }


                                feedback.className =
                                    'dsa-feedback show';


                                feedback.textContent =
                                    (
                                        isCorrect ?
                                        'Correct. ' :
                                        'Not quite. '
                                    ) +
                                    quiz.dataset.why;


                                if (isCorrect) {

                                    completed.add(
                                        Number(
                                            quiz.dataset.lesson
                                        )
                                    );


                                    localStorage.setItem(
                                        storageKey,
                                        JSON.stringify(
                                            [...completed]
                                        )
                                    );


                                    paintProgress();

                                }

                            }
                        );

                    });

            });


        /* =========================================================
           COPY CODE
        ========================================================= */

        document
            .querySelectorAll('.dsa-copy')
            .forEach(button => {

                button.addEventListener(
                    'click',
                    async () => {

                        const code =
                            button
                            .closest('.dsa-body')
                            .querySelector(
                                '.dsa-code code'
                            );


                        if (!code) {
                            return;
                        }


                        try {

                            await navigator.clipboard.writeText(
                                code.textContent
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
           DSA SEARCH
        ========================================================= */

        const search =
            document.querySelector(
                '#dsaSearch'
            );


        const searchResult =
            document.querySelector(
                '#dsaSearchResult'
            );


        const lessonCards =
            document.querySelectorAll(
                '[data-dsa-lesson]'
            );


        if (search) {

            search.addEventListener(
                'input',
                function() {

                    const value =
                        this.value
                        .trim()
                        .toLowerCase();


                    let found = 0;


                    lessonCards.forEach(lesson => {

                        const text =
                            lesson.dataset.searchText ||
                            '';


                        const matches = !value ||
                            text.includes(value);


                        lesson.classList.toggle(
                            'search-hidden',
                            !matches
                        );


                        if (matches) {
                            found++;
                        }

                    });


                    if (value) {

                        searchResult.style.display =
                            'block';


                        searchResult.textContent =
                            found +
                            ' DSA lesson(s) found.';

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

        const navSearch =
            document.querySelector(
                '#dsaNavSearch'
            );


        if (navSearch) {

            navSearch.addEventListener(
                'input',
                function() {

                    const value =
                        this.value
                        .trim()
                        .toLowerCase();


                    document
                        .querySelectorAll(
                            '[data-dsa-nav-link]'
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
           QUIZ AFTER EVERY 10 LESSONS
        ========================================================= */

        document
            .querySelectorAll(
                '[data-dsa-block-quiz]'
            )
            .forEach(quiz => {


                const check =
                    quiz.querySelector(
                        '[data-check-dsa-block]'
                    );


                const reset =
                    quiz.querySelector(
                        '[data-reset-dsa-block]'
                    );


                const result =
                    quiz.querySelector(
                        '[data-dsa-block-result]'
                    );


                if (check) {

                    check.addEventListener(
                        'click',
                        () => {

                            const questions =
                                quiz.querySelectorAll(
                                    '[data-dsa-block-question]'
                                );


                            let score = 0;

                            let answered = 0;


                            questions.forEach(
                                question => {

                                    const selected =
                                        question.querySelector(
                                            'input[type="radio"]:checked'
                                        );


                                    const correct =
                                        Number(
                                            question
                                            .querySelector(
                                                '[data-dsa-correct-answer]'
                                            )
                                            .value
                                        );


                                    const solution =
                                        question.querySelector(
                                            '[data-dsa-block-solution]'
                                        );


                                    if (solution) {
                                        solution.classList.add('show');
                                    }


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

                                }
                            );


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
                                    ' Excellent! You completed this DSA block.';

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
                                    ' Review this block before moving forward.';

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


                            quiz
                                .querySelectorAll(
                                    '[data-dsa-block-solution]'
                                )
                                .forEach(solution => {

                                    solution.classList.remove(
                                        'show'
                                    );

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
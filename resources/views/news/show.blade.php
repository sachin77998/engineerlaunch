{{-- ============================================================
FILE:
resources/views/news/show.blade.php

REPLACE THE EXISTING FILE COMPLETELY WITH THIS FILE.
DO NOT CHANGE EXISTING ROUTES / CONTROLLERS / MODELS.
This view is designed for the existing:
    /latest/{slug}

It keeps the page dynamic using $article.
============================================================ --}}

@extends('layouts.app')

@section('content')

<style>
/* ============================================================
   ASCENDIA — LATEST INTELLIGENCE ARTICLE
   ============================================================ */

:root {
    --ai-navy: #0b2748;
    --ai-navy-2: #123b68;
    --ai-blue: #1769e0;
    --ai-blue-light: #edf5ff;
    --ai-cyan: #08a7c7;
    --ai-green: #159447;
    --ai-orange: #ef8b22;
    --ai-red: #d64545;
    --ai-text: #142d47;
    --ai-muted: #60758c;
    --ai-border: #d9e4ef;
    --ai-bg: #f5f8fc;
    --ai-white: #ffffff;
}

/* ---------- PAGE ---------- */

.ai-page {
    background: var(--ai-bg);
    color: var(--ai-text);
    min-height: 100vh;
    padding: 42px 0 90px;
}

.ai-container {
    width: min(1180px, calc(100% - 40px));
    margin: 0 auto;
}

/* ---------- BREADCRUMB ---------- */

.ai-breadcrumb {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 9px;
    margin-bottom: 28px;
    font-size: 14px;
    color: var(--ai-muted);
}

.ai-breadcrumb a {
    color: var(--ai-blue);
    text-decoration: none;
    font-weight: 700;
}

.ai-breadcrumb a:hover {
    text-decoration: underline;
}

/* ---------- HEADER ---------- */

.ai-header {
    max-width: 980px;
    margin-bottom: 34px;
}

.ai-category {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    background: var(--ai-blue-light);
    border: 1px solid #d3e5ff;
    color: var(--ai-blue);
    border-radius: 5px;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: .9px;
    text-transform: uppercase;
    margin-bottom: 18px;
}

.ai-header h1 {
    margin: 0;
    max-width: 1000px;
    font-size: clamp(38px, 5vw, 64px);
    line-height: 1.06;
    letter-spacing: -2px;
    font-weight: 800;
    color: var(--ai-navy);
}

.ai-subtitle {
    margin: 23px 0 22px;
    max-width: 900px;
    font-size: 19px;
    line-height: 1.7;
    color: #526b83;
}

.ai-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 12px;
    font-size: 14px;
    color: var(--ai-muted);
}

.ai-meta strong {
    color: var(--ai-text);
}

/* ---------- HERO ---------- */

.ai-hero {
    position: relative;
    min-height: 420px;
    overflow: hidden;
    border-radius: 24px;
    margin-bottom: 30px;
    background:
        radial-gradient(circle at 75% 30%, rgba(35, 125, 238, .95), transparent 34%),
        linear-gradient(120deg, #071e39, #123e70 55%, #145bc1);
}

.ai-hero-grid {
    position: absolute;
    inset: 0;
    opacity: .22;
    background-image:
        linear-gradient(rgba(255,255,255,.2) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.2) 1px, transparent 1px);
    background-size: 45px 45px;
}

.ai-hero-content {
    position: relative;
    z-index: 2;
    min-height: 420px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 55px;
}

.ai-hero-inner {
    max-width: 850px;
}

.ai-hero-kicker {
    color: #a9d8ff;
    font-size: 13px;
    letter-spacing: 1.5px;
    font-weight: 800;
    margin-bottom: 18px;
}

.ai-hero-title {
    color: #ffffff;
    font-size: clamp(32px, 4vw, 54px);
    line-height: 1.12;
    font-weight: 800;
    margin-bottom: 22px;
}

.ai-hero-description {
    color: #e7f2ff;
    font-size: 17px;
    line-height: 1.7;
}

/* ---------- QUICK TAKE ---------- */

.ai-two-column {
    display: grid;
    grid-template-columns: 1.25fr .75fr;
    gap: 24px;
    margin-bottom: 34px;
}

.ai-panel {
    background: #ffffff;
    border: 1px solid var(--ai-border);
    border-radius: 20px;
    padding: 32px;
}

.ai-panel-label {
    color: var(--ai-blue);
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    margin-bottom: 12px;
}

.ai-panel h2 {
    margin: 0 0 17px;
    font-size: 27px;
    color: var(--ai-navy);
}

.ai-panel p {
    margin: 0;
    color: var(--ai-muted);
    line-height: 1.8;
    font-size: 16px;
}

/* ---------- FACT BOXES ---------- */

.ai-facts {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-top: 24px;
}

.ai-fact {
    background: #f3f7fc;
    border: 1px solid #e1ebf5;
    border-radius: 12px;
    padding: 17px;
}

.ai-fact-label {
    display: block;
    color: var(--ai-muted);
    font-size: 13px;
    margin-bottom: 5px;
}

.ai-fact-value {
    color: var(--ai-navy);
    font-size: 16px;
    font-weight: 800;
}

/* ---------- SCORE ---------- */

.ai-score-number {
    color: var(--ai-blue);
    font-size: 52px;
    line-height: 1;
    font-weight: 800;
    margin: 10px 0 25px;
}

.ai-score-number span {
    color: #8ba0b5;
    font-size: 20px;
    font-weight: 600;
}

.ai-score-row {
    margin-bottom: 18px;
}

.ai-score-row-top {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 7px;
    font-size: 14px;
}

.ai-score-row-top strong {
    color: var(--ai-green);
}

.ai-score-bar {
    height: 7px;
    border-radius: 10px;
    background: #e8eef5;
    overflow: hidden;
}

.ai-score-fill {
    height: 100%;
    border-radius: inherit;
    background: var(--ai-blue);
}

/* ---------- CONTENT SECTIONS ---------- */

.ai-section {
    padding: 48px 0;
    border-top: 1px solid var(--ai-border);
}

.ai-section-heading {
    display: grid;
    grid-template-columns: 70px 1fr;
    gap: 22px;
}

.ai-section-number {
    font-size: 26px;
    color: #a8b9ca;
    font-weight: 800;
}

.ai-section-label {
    color: var(--ai-blue);
    font-size: 12px;
    letter-spacing: 1.2px;
    font-weight: 800;
    text-transform: uppercase;
    margin-bottom: 10px;
}

.ai-section h2 {
    margin: 0 0 22px;
    color: var(--ai-navy);
    font-size: 34px;
    line-height: 1.2;
}

.ai-section-body {
    max-width: 900px;
}

.ai-section-body p {
    color: #536c84;
    font-size: 17px;
    line-height: 1.9;
    margin: 0 0 18px;
}

.ai-section-body p:last-child {
    margin-bottom: 0;
}

/* ---------- POINT LIST ---------- */

.ai-point-list {
    display: grid;
    gap: 12px;
    margin-top: 24px;
}

.ai-point {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 18px 20px;
    background: #ffffff;
    border: 1px solid var(--ai-border);
    border-radius: 12px;
}

.ai-point-icon {
    width: 27px;
    height: 27px;
    flex: 0 0 27px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: var(--ai-blue-light);
    color: var(--ai-blue);
    font-weight: 800;
}

.ai-point strong {
    display: block;
    margin-bottom: 4px;
    color: var(--ai-navy);
}

.ai-point span {
    color: var(--ai-muted);
    line-height: 1.6;
}

/* ---------- DIAGRAM ---------- */

.ai-diagram-wrapper {
    margin: 32px 0 10px;
    background: #ffffff;
    border: 1px solid var(--ai-border);
    border-radius: 20px;
    padding: 28px;
    overflow-x: auto;
}

.ai-diagram-title {
    text-align: center;
    color: var(--ai-navy);
    font-weight: 800;
    font-size: 18px;
    margin-bottom: 22px;
}

.ai-flow {
    min-width: 760px;
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    align-items: center;
    gap: 14px;
}

.ai-flow-node {
    position: relative;
    text-align: center;
    background: #f5f8fc;
    border: 1px solid #dce7f2;
    border-radius: 14px;
    padding: 20px 12px;
}

.ai-flow-node .icon {
    display: block;
    font-size: 25px;
    margin-bottom: 8px;
}

.ai-flow-node strong {
    display: block;
    font-size: 14px;
    color: var(--ai-navy);
}

.ai-flow-node span {
    display: block;
    margin-top: 5px;
    font-size: 12px;
    color: var(--ai-muted);
}

.ai-flow-arrow {
    position: relative;
    height: 2px;
    background: #b8cadc;
}

.ai-flow-arrow::after {
    content: "";
    position: absolute;
    right: -1px;
    top: -5px;
    border-left: 8px solid #8fa8c0;
    border-top: 6px solid transparent;
    border-bottom: 6px solid transparent;
}

/* ---------- IMPACT MATRIX ---------- */

.ai-impact-box {
    margin-top: 25px;
    background: #ffffff;
    border: 1px solid var(--ai-border);
    border-radius: 20px;
    overflow: hidden;
}

.ai-impact-header,
.ai-impact-row {
    display: grid;
    grid-template-columns: 1.5fr .7fr .7fr;
    gap: 20px;
    align-items: center;
    padding: 17px 22px;
}

.ai-impact-header {
    background: #f1f6fb;
    color: var(--ai-muted);
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .7px;
    font-weight: 800;
}

.ai-impact-row {
    border-top: 1px solid #e8eef5;
    color: var(--ai-text);
}

.ai-impact-row strong {
    color: var(--ai-green);
}

.ai-impact-direction {
    font-size: 14px;
    font-weight: 800;
}

/* ---------- CAREER CARD ---------- */

.ai-career-card {
    background: linear-gradient(135deg, #0a294a, #123e6b);
    color: #ffffff;
    border-radius: 22px;
    padding: 38px;
    margin: 32px 0;
}

.ai-career-card .ai-section-label {
    color: #8ec7ff;
}

.ai-career-card h2 {
    color: #ffffff;
}

.ai-career-card p {
    color: #d8e8f8;
}

/* ---------- SKILLS ---------- */

.ai-skills {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 22px;
}

.ai-skill {
    padding: 11px 17px;
    background: #ffffff;
    border: 1px solid #d5e3f0;
    color: #24415e;
    border-radius: 30px;
    font-size: 14px;
    font-weight: 700;
}

/* ---------- LEARNING ROADMAP ---------- */

.ai-roadmap {
    margin-top: 30px;
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 0;
}

.ai-roadmap-item {
    position: relative;
    text-align: center;
    padding: 12px 8px;
}

.ai-roadmap-item::after {
    content: "";
    position: absolute;
    top: 24px;
    left: 50%;
    width: 100%;
    height: 2px;
    background: #cbd9e7;
    z-index: 0;
}

.ai-roadmap-item:last-child::after {
    display: none;
}

.ai-roadmap-circle {
    position: relative;
    z-index: 2;
    width: 48px;
    height: 48px;
    margin: 0 auto 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: var(--ai-blue);
    color: #ffffff;
    font-weight: 800;
}

.ai-roadmap-item strong {
    display: block;
    color: var(--ai-navy);
    font-size: 13px;
}

/* ---------- ACTION LIST ---------- */

.ai-action-list {
    counter-reset: action;
    display: grid;
    gap: 14px;
    margin-top: 25px;
}

.ai-action {
    counter-increment: action;
    display: grid;
    grid-template-columns: 45px 1fr;
    gap: 15px;
    align-items: center;
    background: #ffffff;
    border: 1px solid var(--ai-border);
    border-radius: 14px;
    padding: 18px;
}

.ai-action::before {
    content: counter(action);
    width: 38px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: var(--ai-blue-light);
    color: var(--ai-blue);
    font-weight: 800;
}

.ai-action strong {
    display: block;
    color: var(--ai-navy);
    margin-bottom: 4px;
}

.ai-action span {
    color: var(--ai-muted);
    line-height: 1.5;
}

/* ---------- JOB CTA ---------- */

.ai-jobs-cta {
    background: #102f50;
    border-radius: 22px;
    padding: 42px;
    color: #ffffff;
    margin: 36px 0;
}

.ai-jobs-cta h2 {
    color: #ffffff;
    margin: 0 0 12px;
    font-size: 30px;
}

.ai-jobs-cta p {
    color: #d5e5f4;
    max-width: 750px;
    line-height: 1.7;
    margin-bottom: 22px;
}

.ai-job-button {
    display: inline-flex;
    align-items: center;
    padding: 13px 20px;
    background: var(--ai-blue);
    color: #ffffff;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 800;
}

.ai-job-button:hover {
    color: #ffffff;
    background: #0d58c7;
}

/* ---------- LIVE JOBS ---------- */

.ai-job-section {
    margin: 45px 0;
}

.ai-job-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
    margin-top: 20px;
}

.ai-job-card {
    background: #ffffff;
    border: 1px solid var(--ai-border);
    border-radius: 16px;
    padding: 23px;
}

.ai-job-company {
    color: var(--ai-muted);
    font-size: 13px;
    margin-bottom: 12px;
}

.ai-job-card h3 {
    color: var(--ai-navy);
    font-size: 18px;
    line-height: 1.35;
    margin-bottom: 12px;
}

.ai-job-location {
    color: var(--ai-muted);
    font-size: 14px;
    margin-bottom: 16px;
}

.ai-job-card a {
    color: var(--ai-blue);
    text-decoration: none;
    font-weight: 800;
}

/* ---------- RELATED INTELLIGENCE ---------- */

.ai-related {
    margin: 50px 0;
}

.ai-related-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
    margin-top: 20px;
}

.ai-related-card {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 190px;
    background: #ffffff;
    border: 1px solid var(--ai-border);
    border-radius: 16px;
    padding: 24px;
}

.ai-related-card h3 {
    color: var(--ai-navy);
    font-size: 18px;
    line-height: 1.4;
    margin: 0 0 20px;
}

.ai-related-card a {
    color: var(--ai-blue);
    text-decoration: none;
    font-weight: 800;
}

/* ---------- SOURCE ---------- */

.ai-source {
    background: #ffffff;
    border: 1px solid var(--ai-border);
    border-radius: 20px;
    padding: 32px;
    margin-top: 35px;
}

.ai-source-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px 35px;
    margin-top: 20px;
}

.ai-source-row {
    padding: 13px 0;
    border-bottom: 1px solid #edf2f7;
}

.ai-source-row strong {
    color: var(--ai-navy);
}

.ai-source-row span {
    color: var(--ai-muted);
}

.ai-source-link {
    display: inline-block;
    margin-top: 22px;
    color: var(--ai-blue);
    font-weight: 800;
    text-decoration: none;
}

/* ---------- RESPONSIVE ---------- */

@media (max-width: 900px) {

    .ai-two-column {
        grid-template-columns: 1fr;
    }

    .ai-job-grid,
    .ai-related-grid {
        grid-template-columns: 1fr 1fr;
    }

    .ai-roadmap {
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
    }

    .ai-roadmap-item::after {
        display: none;
    }

}

@media (max-width: 650px) {

    .ai-page {
        padding: 25px 0 60px;
    }

    .ai-container {
        width: min(100% - 28px, 1180px);
    }

    .ai-header h1 {
        font-size: 38px;
        letter-spacing: -1px;
    }

    .ai-subtitle {
        font-size: 17px;
    }

    .ai-hero,
    .ai-hero-content {
        min-height: 330px;
    }

    .ai-hero-content {
        padding: 30px 22px;
    }

    .ai-panel {
        padding: 23px;
    }

    .ai-facts {
        grid-template-columns: 1fr;
    }

    .ai-section {
        padding: 35px 0;
    }

    .ai-section-heading {
        grid-template-columns: 1fr;
        gap: 4px;
    }

    .ai-section-number {
        font-size: 18px;
    }

    .ai-section h2 {
        font-size: 28px;
    }

    .ai-impact-header,
    .ai-impact-row {
        grid-template-columns: 1.4fr .7fr .7fr;
        gap: 8px;
        padding: 14px;
        font-size: 13px;
    }

    .ai-roadmap {
        grid-template-columns: repeat(2, 1fr);
    }

    .ai-job-grid,
    .ai-related-grid {
        grid-template-columns: 1fr;
    }

    .ai-career-card,
    .ai-jobs-cta {
        padding: 28px 22px;
    }

    .ai-source-grid {
        grid-template-columns: 1fr;
    }

}
</style>


<div class="ai-page">

    <div class="ai-container">

        {{-- =====================================================
             BREADCRUMB
        ====================================================== --}}

        <div class="ai-breadcrumb">

            <a href="{{ route('news.index') }}">
                ← Latest Intelligence
            </a>

            <span>/</span>

            <span>
                {{ $article->category->name ?? 'Technology' }}
            </span>

        </div>


        {{-- =====================================================
             ARTICLE HEADER
        ====================================================== --}}

        <header class="ai-header">

            <div class="ai-category">

                <span>●</span>

                {{ strtoupper($article->category->name ?? 'Technology Intelligence') }}

            </div>

            <h1>
                {{ $article->title }}
            </h1>

            <p class="ai-subtitle">
                {{ $article->excerpt
                    ?? $article->summary
                    ?? 'Understand what changed, why it matters and what it means for technology, industry and careers.' }}
            </p>

            <div class="ai-meta">

                <span>
                    Source:
                    <strong>
                        {{ $article->source->name ?? 'Ascendia Intelligence' }}
                    </strong>
                </span>

                <span>•</span>

                <span>
                    {{ optional($article->source_published_at ?? $article->published_at)->format('M d, Y') }}
                </span>

                <span>•</span>

                <span>
                    Technology Intelligence
                </span>

            </div>

        </header>


        {{-- =====================================================
             HERO VISUAL
        ====================================================== --}}

        <div class="ai-hero">

            <div class="ai-hero-grid"></div>

            <div class="ai-hero-content">

                <div class="ai-hero-inner">

                    <div class="ai-hero-kicker">
                        LATEST INTELLIGENCE
                    </div>

                    <div class="ai-hero-title">
                        What changed.<br>
                        Why it matters.<br>
                        What it means for your career.
                    </div>

                    <div class="ai-hero-description">
                        A structured view of the technology trend,
                        business impact, career opportunities and
                        skills worth watching.
                    </div>

                </div>

            </div>

        </div>


        {{-- =====================================================
             QUICK TAKE + INTELLIGENCE SCORE
        ====================================================== --}}

        <div class="ai-two-column">

            <div class="ai-panel">

                <div class="ai-panel-label">
                    Quick Take
                </div>

                <h2>
                    What you need to know
                </h2>

                <p>
                    {{ $article->why_it_matters
                        ?? $article->industry_impact
                        ?? 'This intelligence highlights the most important industry and career implications without repeating the source summary.' }}
                </p>


                <div class="ai-facts">

                    <div class="ai-fact">

                        <span class="ai-fact-label">
                            Career impact
                        </span>

                        <span class="ai-fact-value">
                            {{ $article->career_signal ?? 'High' }}
                        </span>

                    </div>


                    <div class="ai-fact">

                        <span class="ai-fact-label">
                            Market direction
                        </span>

                        <span class="ai-fact-value">
                            {{ $article->market_signal ?? 'Growing' }}
                        </span>

                    </div>


                    <div class="ai-fact">

                        <span class="ai-fact-label">
                            Skills identified
                        </span>

                        <span class="ai-fact-value">
                            {{ $skills->count() }}
                        </span>

                    </div>


                    <div class="ai-fact">

                        <span class="ai-fact-label">
                            Intelligence type
                        </span>

                        <span class="ai-fact-value">
                            Career + Industry
                        </span>

                    </div>

                </div>

            </div>


            <div class="ai-panel">

                <div class="ai-panel-label">
                    Intelligence Score
                </div>

                <div class="ai-score-number">
                    {{ $intelligence['overall'] }}
                    <span>/100</span>
                </div>


                @php
                    $scores = [
                        'Market impact' => $intelligence['market'],
                        'Career impact' => $intelligence['career'],
                        'Technology impact' => $intelligence['technology'],
                        'Hiring impact' => $intelligence['hiring'],
                    ];
                @endphp


                @foreach($scores as $label => $score)

                    <div class="ai-score-row">

                        <div class="ai-score-row-top">

                            <span>
                                {{ $label }}
                            </span>

                            <strong>
                                {{ $score }}
                            </strong>

                        </div>

                        <div class="ai-score-bar">

                            <div
                                class="ai-score-fill"
                                style="width: {{ min(100, max(0, (int)$score)) }}%"
                            ></div>

                        </div>

                    </div>

                @endforeach

            </div>

        </div>


        {{-- =====================================================
             01 WHAT HAPPENED
        ====================================================== --}}

        <section class="ai-section">

            <div class="ai-section-heading">

                <div class="ai-section-number">
                    01
                </div>

                <div>

                    <div class="ai-section-label">
                        The News
                    </div>

                    <h2>
                        What happened?
                    </h2>

                    <div class="ai-section-body">

                        <p>
                            {{ $article->what_happened
                                ?? $article->summary
                                ?? 'The latest development is becoming relevant to technology teams, businesses and professionals as adoption continues to increase.' }}
                        </p>

                    </div>

                </div>

            </div>

        </section>


        {{-- =====================================================
             DIAGRAM — WHAT IS CHANGING
        ====================================================== --}}

        <div class="ai-diagram-wrapper">

            <div class="ai-diagram-title">
                How this trend moves through the technology ecosystem
            </div>

            <div class="ai-flow">

                <div class="ai-flow-node">
                    <span class="icon">💡</span>
                    <strong>Technology</strong>
                    <span>New capability</span>
                </div>

                <div class="ai-flow-arrow"></div>

                <div class="ai-flow-node">
                    <span class="icon">🏢</span>
                    <strong>Companies</strong>
                    <span>Adoption & investment</span>
                </div>

                <div class="ai-flow-arrow"></div>

                <div class="ai-flow-node">
                    <span class="icon">⚙️</span>
                    <strong>Engineering</strong>
                    <span>New workflows</span>
                </div>

                <div class="ai-flow-arrow"></div>

                <div class="ai-flow-node">
                    <span class="icon">👨‍💻</span>
                    <strong>Careers</strong>
                    <span>Skills & jobs</span>
                </div>

            </div>

        </div>


        {{-- =====================================================
             02 WHY IT MATTERS
        ====================================================== --}}

        <section class="ai-section">

            <div class="ai-section-heading">

                <div class="ai-section-number">
                    02
                </div>

                <div>

                    <div class="ai-section-label">
                        Industry Intelligence
                    </div>

                    <h2>
                        Why does it matter?
                    </h2>

                    <div class="ai-section-body">

                        <p>
                            {{ $article->why_it_matters
                                ?? $article->industry_impact
                                ?? 'The development can influence technology adoption, investment priorities, engineering practices and workforce demand.' }}
                        </p>

                    </div>


                    <div class="ai-point-list">

                        <div class="ai-point">

                            <div class="ai-point-icon">
                                ✓
                            </div>

                            <div>

                                <strong>
                                    Technology adoption
                                </strong>

                                <span>
                                    Companies evaluate new tools,
                                    platforms and infrastructure as
                                    part of their technology strategy.
                                </span>

                            </div>

                        </div>


                        <div class="ai-point">

                            <div class="ai-point-icon">
                                ✓
                            </div>

                            <div>

                                <strong>
                                    Engineering workflows
                                </strong>

                                <span>
                                    Development teams may change how
                                    they build, test, deploy and
                                    maintain applications.
                                </span>

                            </div>

                        </div>


                        <div class="ai-point">

                            <div class="ai-point-icon">
                                ✓
                            </div>

                            <div>

                                <strong>
                                    Workforce demand
                                </strong>

                                <span>
                                    New technology adoption can create
                                    demand for new combinations of
                                    engineering and business skills.
                                </span>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </section>


        {{-- =====================================================
             03 INDUSTRY IMPACT
        ====================================================== --}}

        <section class="ai-section">

            <div class="ai-section-heading">

                <div class="ai-section-number">
                    03
                </div>

                <div>

                    <div class="ai-section-label">
                        Industry Impact
                    </div>

                    <h2>
                        What is changing across the industry?
                    </h2>

                    <div class="ai-section-body">

                        <p>
                            {{ $article->industry_impact
                                ?? 'Technology developments increasingly affect investment, infrastructure, product development, hiring and the skills companies expect from professionals.' }}
                        </p>

                    </div>


                    <div class="ai-impact-box">

                        <div class="ai-impact-header">

                            <div>
                                Area
                            </div>

                            <div>
                                Impact
                            </div>

                            <div>
                                Direction
                            </div>

                        </div>


                        <div class="ai-impact-row">

                            <div>
                                Technology adoption
                            </div>

                            <strong>
                                High
                            </strong>

                            <span class="ai-impact-direction">
                                ↑
                            </span>

                        </div>


                        <div class="ai-impact-row">

                            <div>
                                Engineering demand
                            </div>

                            <strong>
                                High
                            </strong>

                            <span class="ai-impact-direction">
                                ↑
                            </span>

                        </div>


                        <div class="ai-impact-row">

                            <div>
                                Infrastructure
                            </div>

                            <strong>
                                High
                            </strong>

                            <span class="ai-impact-direction">
                                ↑
                            </span>

                        </div>


                        <div class="ai-impact-row">

                            <div>
                                Traditional workflows
                            </div>

                            <strong>
                                Changing
                            </strong>

                            <span class="ai-impact-direction">
                                →
                            </span>

                        </div>

                    </div>

                </div>

            </div>

        </section>


        {{-- =====================================================
             04 CAREER INTELLIGENCE
        ====================================================== --}}

        <section class="ai-career-card">

            <div class="ai-section-label">
                Career Intelligence
            </div>

            <h2>
                What does this mean for your career?
            </h2>

            <p>
                {{ $article->career_impact
                    ?? $article->student_impact
                    ?? 'Technology changes do not affect every role equally. Understanding which skills are becoming more valuable can help students and professionals plan their next career move.' }}
            </p>


            <div class="ai-impact-box">

                <div class="ai-impact-header">

                    <div>
                        Role
                    </div>

                    <div>
                        Impact
                    </div>

                    <div>
                        Signal
                    </div>

                </div>@php $careerRoles = $article->careerInsights->map(fn ($insight) => ['role' => $insight->role, 'impact' => ucwords(str_replace('_', ' ', $insight->impact_level)), 'signal' => in_array($insight->impact_level, ['high', 'very_high']) ? 'UP' : 'STABLE']); if ($careerRoles->isEmpty()) { $careerRoles = $roles->map(fn ($role) => ['role' => $role, 'impact' => ucfirst($article->career_impact_level ?: 'medium'), 'signal' => $article->career_impact_level === 'high' ? 'UP' : 'STABLE']); } @endphp@foreach($careerRoles as $role)

                    <div class="ai-impact-row">

                        <div>
                            {{ $role['role'] }}
                        </div>

                        <strong>
                            {{ $role['impact'] }}
                        </strong>

                        <span class="ai-impact-direction">
                            {{ $role['signal'] }}
                        </span>

                    </div>

                @endforeach

            </div>

        </section>


        {{-- =====================================================
             05 SKILLS
        ====================================================== --}}

        <section class="ai-section">

            <div class="ai-section-heading">

                <div class="ai-section-number">
                    05
                </div>

                <div>

                    <div class="ai-section-label">
                        Skills To Watch
                    </div>

                    <h2>
                        Skills worth learning
                    </h2>

                    <div class="ai-section-body">

                        <p>
                            The most valuable response to a technology
                            trend is understanding which skills allow
                            you to work with it in real production
                            environments.
                        </p>

                    </div>


                    <div class="ai-skills">

                        @foreach($skills as $skill)

                            <span class="ai-skill">
                                {{ is_array($skill) ? ($skill['name'] ?? '') : $skill }}
                            </span>

                        @endforeach

                    </div>

                </div>

            </div>

        </section>


        {{-- =====================================================
             LEARNING ROADMAP
        ====================================================== --}}

        <div class="ai-panel">

            <div class="ai-panel-label">
                Recommended Learning Path
            </div>

            <h2>
                From fundamentals to production
            </h2>

            <div class="ai-roadmap">

                <div class="ai-roadmap-item">

                    <div class="ai-roadmap-circle">
                        1
                    </div>

                    <strong>
                        Programming
                    </strong>

                </div>


                <div class="ai-roadmap-item">

                    <div class="ai-roadmap-circle">
                        2
                    </div>

                    <strong>
                        APIs
                    </strong>

                </div>


                <div class="ai-roadmap-item">

                    <div class="ai-roadmap-circle">
                        3
                    </div>

                    <strong>
                        AI / LLM
                    </strong>

                </div>


                <div class="ai-roadmap-item">

                    <div class="ai-roadmap-circle">
                        4
                    </div>

                    <strong>
                        Cloud
                    </strong>

                </div>


                <div class="ai-roadmap-item">

                    <div class="ai-roadmap-circle">
                        5
                    </div>

                    <strong>
                        DevOps
                    </strong>

                </div>


                <div class="ai-roadmap-item">

                    <div class="ai-roadmap-circle">
                        6
                    </div>

                    <strong>
                        Production
                    </strong>

                </div>

            </div>

        </div>


        {{-- =====================================================
             06 WHAT SHOULD YOU DO
        ====================================================== --}}

        <section class="ai-section">

            <div class="ai-section-heading">

                <div class="ai-section-number">
                    06
                </div>

                <div>

                    <div class="ai-section-label">
                        Career Action
                    </div>

                    <h2>
                        Turn this signal into action
                    </h2>


                    <div class="ai-action-list">

                        <div class="ai-action">

                            <div>

                                <strong>
                                    Strengthen engineering fundamentals
                                </strong>

                                <span>
                                    Keep architecture, databases,
                                    security, testing and debugging
                                    skills strong.
                                </span>

                            </div>

                        </div>


                        <div class="ai-action">

                            <div>

                                <strong>
                                    Learn the technology behind the trend
                                </strong>

                                <span>
                                    Understand APIs, platforms,
                                    infrastructure and real-world
                                    implementation patterns.
                                </span>

                            </div>

                        </div>


                        <div class="ai-action">

                            <div>

                                <strong>
                                    Build production projects
                                </strong>

                                <span>
                                    Move beyond tutorials and build
                                    applications that solve practical
                                    business problems.
                                </span>

                            </div>

                        </div>


                        <div class="ai-action">

                            <div>

                                <strong>
                                    Add complementary skills
                                </strong>

                                <span>
                                    Combine your primary development
                                    skill with cloud, DevOps, data,
                                    security or AI capabilities.
                                </span>

                            </div>

                        </div>


                        <div class="ai-action">

                            <div>

                                <strong>
                                    Track hiring demand
                                </strong>

                                <span>
                                    Watch which roles and skills
                                    companies are actually hiring for.
                                </span>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </section>


        {{-- =====================================================
             TECHNOLOGY STACK
        ====================================================== --}}

        <section class="ai-section">

            <div class="ai-section-heading">

                <div class="ai-section-number">
                    07
                </div>

                <div>

                    <div class="ai-section-label">
                        Technology Stack
                    </div>

                    <h2>
                        Technologies connected to this trend
                    </h2>


                    <div class="ai-skills">

                        <span class="ai-skill">Python</span>
                        <span class="ai-skill">PHP</span>
                        <span class="ai-skill">Laravel</span>
                        <span class="ai-skill">Java</span>
                        <span class="ai-skill">Spring Boot</span>
                        <span class="ai-skill">Node.js</span>
                        <span class="ai-skill">REST APIs</span>
                        <span class="ai-skill">Docker</span>
                        <span class="ai-skill">Kubernetes</span>
                        <span class="ai-skill">Redis</span>
                        <span class="ai-skill">Kafka</span>
                        <span class="ai-skill">Cloud</span>
                        <span class="ai-skill">Security</span>

                    </div>

                </div>

            </div>

        </section>


        {{-- =====================================================
             JOBS CTA
        ====================================================== --}}

        @php
            $matchingJobs = $relatedJobCount;
        @endphp


        <div class="ai-jobs-cta">

            <div class="ai-section-label">
                Career Opportunities
            </div>

            <h2>
                Jobs connected to this intelligence
            </h2>

            <p>

                @if($matchingJobs > 0)

                    {{ number_format($matchingJobs) }}
                    active roles match the skills and career signals
                    identified in this intelligence.

                @else

                    Explore jobs connected to the skills,
                    technologies and career trends discussed
                    in this intelligence.

                @endif

            </p>

            <a
                href="{{ route('home') }}#jobs"
                class="ai-job-button"
            >
                View related jobs →
            </a>

        </div>


        {{-- =====================================================
             LIVE OPPORTUNITIES
        ====================================================== --}}

        @if(isset($relatedJobs) && $relatedJobs->count())

            <section class="ai-job-section">

                <div class="ai-section-label">
                    Live Opportunities
                </div>

                <h2>
                    Roles you may want to explore
                </h2>


                <div class="ai-job-grid">

                    @foreach($relatedJobs->take(6) as $job)

                        <article class="ai-job-card">

                            <div class="ai-job-company">
                                {{ $job->company->name
                                    ?? $job->company_name
                                    ?? 'Company' }}
                            </div>

                            <h3>
                                {{ $job->title }}
                            </h3>

                            <div class="ai-job-location">

                                {{ $job->location
                                    ?? $job->city
                                    ?? 'India' }}

                            </div>

                            <a
                                href="{{ $job->slug ? route('jobs.show', $job->slug) : $job->external_url }}"
                            >
                                View role →
                            </a>

                        </article>

                    @endforeach

                </div>

            </section>

        @endif


        {{-- =====================================================
             RELATED INTELLIGENCE
        ====================================================== --}}

        @if(isset($relatedArticles) && $relatedArticles->count())

            <section class="ai-related">

                <div class="ai-section-label">
                    Related Intelligence
                </div>

                <h2>
                    Continue exploring
                </h2>


                <div class="ai-related-grid">

                    @foreach($relatedArticles->take(6) as $related)

                        <article class="ai-related-card">

                            <h3>
                                {{ $related->title }}
                            </h3>

                            <a
                                href="{{ route('news.show', $related->slug) }}"
                            >
                                Read analysis →
                            </a>

                        </article>

                    @endforeach

                </div>

            </section>

        @endif


        {{-- =====================================================
             SOURCE & VERIFICATION
        ====================================================== --}}

        <section class="ai-source">

            <div class="ai-section-label">
                Source & Verification
            </div>

            <h2>
                Credibility and transparency
            </h2>

            <div class="ai-source-grid">

                <div class="ai-source-row">

                    <strong>
                        Source
                    </strong>

                    <br>

                    <span>
                        {{ $article->source->name
                            ?? 'Ascendia Intelligence' }}
                    </span>

                </div>


                <div class="ai-source-row">

                    <strong>
                        Published
                    </strong>

                    <br>

                    <span>
                        {{ optional($article->source_published_at ?? $article->published_at)->format('M d, Y H:i') }}
                    </span>

                </div>


                <div class="ai-source-row">

                    <strong>
                        Analysis
                    </strong>

                    <br>

                    <span>
                        Industry + Career Intelligence
                    </span>

                </div>


                <div class="ai-source-row">

                    <strong>
                        Last updated
                    </strong>

                    <br>

                    <span>
                        {{ optional($article->updated_at)->format('M d, Y H:i') }}
                    </span>

                </div>

            </div>


            @if(filter_var($article->source_url, FILTER_VALIDATE_URL) && in_array(parse_url($article->source_url, PHP_URL_SCHEME), ['http', 'https'], true))

                <a
                    href="{{ $article->source_url }}"
                    target="_blank"
                    rel="noopener noreferrer nofollow"
                    class="ai-source-link"
                >
                    Open original source ↗
                </a>

            @endif

        </section>

    </div>

</div>

@endsection

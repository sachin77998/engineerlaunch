@if($paginator->hasPages())

<nav
    class="industrial-pagination"
    aria-label="{{ $label }} pages">

    <style>
        .industrial-pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .industrial-pagination-side {
            min-width: 110px;
        }

        .industrial-pagination-side:last-child {
            text-align: right;
        }

        .industrial-pagination a {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            min-height: 38px;
            padding: 8px 14px;
            border: 1px solid #dbe3ec;
            border-radius: 10px;
            background: #fff;
            color: #334155;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
            transition: all .18s ease;
        }

        .industrial-pagination a:hover {
            border-color: #93c5fd;
            background: #eff6ff;
            color: #1d4ed8;
        }

        .industrial-pagination-current {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 36px;
            padding: 7px 13px;
            border-radius: 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
        }

        .industrial-pagination-current strong {
            color: #0f172a;
            margin: 0 3px;
        }

        @media (max-width: 520px) {
            .industrial-pagination {
                gap: 8px;
            }

            .industrial-pagination-side {
                min-width: auto;
            }

            .industrial-pagination a {
                padding: 8px 10px;
            }

            .industrial-pagination a span {
                display: none;
            }

            .industrial-pagination-current {
                padding: 7px 9px;
            }
        }
    </style>

    <div class="industrial-pagination-side">

        @if($paginator->previousPageUrl())

        <a
            data-industrial-link
            href="{{ $paginator->previousPageUrl() }}"
            aria-label="Previous {{ $label }} page">
            <span>&larr;</span>
            <span>Previous</span>
        </a>

        @endif

    </div>


    <div class="industrial-pagination-current">

        Page

        <strong>
            {{ $paginator->currentPage() }}
        </strong>

        of

        <strong>
            {{ $paginator->lastPage() }}
        </strong>

    </div>


    <div class="industrial-pagination-side">

        @if($paginator->nextPageUrl())

        <a
            data-industrial-link
            href="{{ $paginator->nextPageUrl() }}"
            aria-label="Next {{ $label }} page">
            <span>Next</span>
            <span>&rarr;</span>
        </a>

        @endif

    </div>

</nav>

@endif
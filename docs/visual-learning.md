# Visual learning

The active learning hub and all seven tracks use a shared presentation layer. Track-level diagrams and comparisons give context; tutorial summaries and supplied interview answers appear as clear points, while SQL stays in code blocks. Existing exercises and quizzes remain available.

- Recall mode hides supplied answers until Reveal is selected.
- Practice notes are saved in localStorage for the current browser and page. They are not uploaded or graded.
- Questions without supplied answers are explicitly labelled practice questions. The layout does not invent an answer or mark an unfinished answer correct.
- Tables have captions and column headers, and scroll within their panels on small screens. Diagrams are ordered lists with responsive connectors.
- Main job, company, industry, technology-news and resume pages have a collapsible workflow and key-terms guide.

## Content locations

`config/learning_visuals.php` contains track diagrams, comparison rows and source links. `LearningPresentation::blocks()` preserves existing answer content while separating prose and code. Shared components live in `resources/views/learning/partials/visual-*`, `answer-blocks` and `lesson-recap`. The project page guide is `resources/views/partials/page-guide.blade.php`.

These are track overviews, not bespoke technical diagrams for every individual question. Topic-specific diagrams can replace the overview through additional curated content; existing practice-only questions still need authored solutions if they are to become answered lessons.

## Checks

`php artisan test --filter="Industrial|VisualLearning"`

Browser checks cover desktop/mobile layouts, answer reveal, notes persistence, SQL and tutorial rendering, and JavaScript errors.

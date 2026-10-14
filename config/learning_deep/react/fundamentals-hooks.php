<?php

// React › Fundamentals and Hooks, in depth.
return [

'What is React?' => <<<'MD'
## What it is
React is a JavaScript library for building user interfaces out of components. You describe what the screen should look like for the current data (state and props), and React works out the smallest set of DOM changes needed to get there. It was released by Facebook (Meta) in 2013 and is used by Facebook, Instagram, Netflix, Swiggy, Flipkart and most modern dashboards.

- **Declarative:** you write `if (loading) return <Spinner/>`, not "find the div, remove its children, append a spinner".
- **Component-based:** a page is a tree of small, reusable pieces (`<JobCard>`, `<SearchBar>`, `<Pagination>`).
- **One-way data flow:** data goes down from parent to child through props; children ask parents to change data through callback props.
- **Library, not framework:** React only handles the view. Routing (React Router), data fetching (TanStack Query), forms and global state come from other libraries or frameworks such as Next.js.

## A first component
```jsx
import { useState } from 'react';

export default function JobCounter() {
  const [applied, setApplied] = useState(0);
  return (
    <div className="card">
      <p>You applied to {applied} jobs today</p>
      <button onClick={() => setApplied(applied + 1)}>Apply</button>
    </div>
  );
}
```
Each click calls `setApplied`; React re-runs the function, compares the new output with the old one and updates only the text inside `<p>`.

## How rendering works
1. **Render:** React calls your component functions to get a tree of elements.
2. **Reconcile:** it compares (diffs) the new tree with the previous one.
3. **Commit:** it applies the minimal DOM changes, then runs effects.

## Interview points
- React is a UI library; Angular is a full framework with DI, router and forms built in.
- It is fast mainly because it batches updates and touches the real DOM as little as possible.
- React 18 added concurrent rendering (`startTransition`), automatic batching and streaming SSR.

## Summary
React = components + state + declarative rendering. You describe the UI for each state, and React keeps the DOM in sync.
MD,

'What is JSX?' => <<<'MD'
## What it is
JSX (JavaScript XML) is a syntax extension that lets you write HTML-like markup inside JavaScript. Browsers do not understand JSX; a compiler (Babel, SWC, esbuild through Vite) turns it into plain function calls.

```jsx
const el = <h1 className="title">Hello {user.name}</h1>;
```
compiles to
```js
const el = jsx('h1', { className: 'title', children: ['Hello ', user.name] });
```
So JSX is just a nicer way of creating React elements, which are plain objects describing what to render.

## Rules of JSX
- Return **one root element**; wrap siblings in a `<div>` or a fragment `<>...</>`.
- Use `className` instead of `class`, `htmlFor` instead of `for`, camelCase events (`onClick`).
- Close every tag: `<img />`, `<input />`.
- `{ }` holds any JavaScript **expression** (not statements): variables, function calls, ternaries, `map`.
- Inline styles are objects: `style={{ color: 'red', fontSize: 14 }}`.

## Conditional rendering and lists
```jsx
function JobList({ jobs, loading }) {
  if (loading) return <p>Loading…</p>;
  return (
    <>
      {jobs.length === 0 && <p>No jobs found</p>}
      <ul>
        {jobs.map(job => (
          <li key={job.id}>
            {job.title} {job.remote ? '(Remote)' : `(${job.city})`}
          </li>
        ))}
      </ul>
    </>
  );
}
```
The `key` must be a stable, unique id so React can match list items between renders. Using the array index breaks when items are inserted, removed or sorted.

## Security
JSX escapes values automatically, so `{comment}` containing `<script>` is shown as text, which prevents XSS. Only `dangerouslySetInnerHTML` bypasses this; sanitise the HTML (DOMPurify) before using it.

## Summary
JSX is syntactic sugar for `React.createElement`/`jsx()` calls: HTML-like, but with JavaScript expressions in braces, camelCase attributes and automatic escaping.
MD,

'What are components?' => <<<'MD'
## What they are
A component is a reusable, self-contained piece of UI. In modern React a component is a **function** that receives `props` and returns JSX. Component names start with a capital letter so React can tell `<JobCard>` (component) from `<div>` (HTML tag).

```jsx
function JobCard({ title, company, salary, onApply }) {
  return (
    <article className="job-card">
      <h3>{title}</h3>
      <p>{company}</p>
      {salary && <p>₹{salary.toLocaleString('en-IN')} per year</p>}
      <button onClick={onApply}>Apply</button>
    </article>
  );
}

function JobsPage({ jobs }) {
  return jobs.map(j => <JobCard key={j.id} {...j} onApply={() => apply(j.id)} />);
}
```

## Function versus class components
| | Function component | Class component |
|---|---|---|
| Syntax | plain function | `class X extends React.Component` with `render()` |
| State | `useState`, `useReducer` | `this.state`, `this.setState` |
| Lifecycle | `useEffect` | `componentDidMount`, `componentDidUpdate`, `componentWillUnmount` |
| Today | recommended | legacy code; still needed for error boundaries |

## Kinds of components
- **Presentational:** only display props (`<Avatar>`, `<Badge>`).
- **Container/smart:** fetch data and hold state, then pass it to presentational children.
- **Layout:** arrange children (`<Sidebar>`, `<Grid>`) using the `children` prop.
- **Composition over inheritance:** React never extends components; you combine them.

```jsx
function Card({ title, children }) {
  return <section className="card"><h2>{title}</h2>{children}</section>;
}
<Card title="Profile"><ProfileForm /></Card>
```

## Good component design
- One responsibility per component; split when a file grows past ~200 lines.
- Keep components pure: same props give the same output, no side effects during render.
- Lift shared state to the nearest common parent.

## Summary
Components are functions from props to UI. Build pages by composing small, pure, reusable components.
MD,

'Props versus state?' => <<<'MD'
## The difference
| | Props | State |
|---|---|---|
| Owner | the parent | the component itself |
| Changeable by the component? | no, read-only | yes, through its setter |
| Purpose | configure a child | remember data that changes over time |
| Causes re-render when changed | yes (parent re-renders with new props) | yes |

**Props** are arguments passed into a component, like function parameters. **State** is the component's own memory, kept by React between renders.

## Example
```jsx
function SalaryFilter({ min, onChange }) {          // min and onChange are props
  return <input type="number" value={min} onChange={e => onChange(Number(e.target.value))} />;
}

function JobSearch() {
  const [minSalary, setMinSalary] = useState(0);    // state lives here
  return (
    <>
      <SalaryFilter min={minSalary} onChange={setMinSalary} />
      <Results minSalary={minSalary} />
    </>
  );
}
```
`JobSearch` owns the state. Both children receive it as props. When the input changes, the child calls `onChange`, the parent updates its state, and both children re-render with the new value. This is **one-way data flow**.

## Rules
- Never modify props: `props.title = 'x'` is a bug.
- Never modify state directly: `state.items.push(x)` does not re-render; create a new array: `setItems([...items, x])`.
- Do not copy props into state (`useState(props.value)`) unless you really want an initial value only; the state will not follow later prop changes.
- Keep state minimal: compute derived values (`filteredJobs`, `total`) during render instead of storing them.

## Summary
Props flow down and are read-only; state is local, mutable through setters, and triggers re-renders. Shared data is held as state in a common parent and passed down as props.
MD,

'What is the virtual DOM?' => <<<'MD'
## What it is
The virtual DOM is a lightweight JavaScript object tree that describes the UI. When state changes, React builds a new tree, compares it with the previous tree (**diffing** or **reconciliation**) and applies only the differences to the real browser DOM.

Real DOM operations (layout, paint) are expensive; comparing plain objects in memory is cheap. Batching many changes into one minimal DOM update keeps the page fast.

## How the diff works
React's algorithm is O(n) thanks to two assumptions:
1. **Different element types produce different trees.** `<div>` replaced by `<section>` destroys the old subtree and builds a new one (state inside is lost).
2. **Keys identify list items.** With `key={job.id}`, React knows which item moved, was added or removed.

```jsx
// Before: [A, B, C]   After: [Z, A, B, C]
{jobs.map(j => <JobRow key={j.id} job={j} />)}   // React inserts one row
{jobs.map((j, i) => <JobRow key={i} job={j} />)} // React thinks every row changed
```

## Render and commit phases
- **Render phase:** call components, build the new virtual tree, diff. Can be paused or restarted in React 18 (concurrent rendering).
- **Commit phase:** apply DOM mutations, run `useLayoutEffect`, then `useEffect`. Never interrupted.

## Fiber
Since React 16 the reconciler is called **Fiber**. Each element becomes a fiber node that represents a unit of work, which lets React split rendering into chunks, prioritise urgent updates (typing) over less urgent ones (rendering a big list) and resume work later.

## Common misconception
The virtual DOM is not "faster than the DOM" by itself; hand-written DOM code can be faster. Its benefit is that you write simple declarative code and still get reasonably minimal updates.

## Summary
Virtual DOM = in-memory UI tree. React diffs old and new trees using element types and keys, then commits the minimal changes to the real DOM.
MD,

'How does useState work?' => <<<'MD'
## What it is
`useState` gives a function component a piece of state that survives re-renders.

```jsx
const [count, setCount] = useState(0);
```
- `count` is the current value for this render.
- `setCount(newValue)` schedules a re-render with the new value.
- `0` is the initial value, used only on the first render.

## How React stores it
React keeps a list of hooks for each component instance and matches them **by call order**. That is why hooks must be called at the top level, never inside conditions or loops:
```jsx
if (loggedIn) { const [x] = useState(); }   // wrong: order changes between renders
```

## Updates are asynchronous and batched
```jsx
function handleClick() {
  setCount(count + 1);
  setCount(count + 1);   // still count + 1: both read the same old value
}
function handleClickFixed() {
  setCount(c => c + 1);
  setCount(c => c + 1);  // + 2: functional update uses the latest value
}
```
Use the **functional form** whenever the new state depends on the old state.

## Objects and arrays
State must be replaced, not mutated:
```jsx
const [form, setForm] = useState({ name: '', email: '' });
setForm(prev => ({ ...prev, email: 'a@b.com' }));

const [skills, setSkills] = useState([]);
setSkills(prev => [...prev, 'React']);               // add
setSkills(prev => prev.filter(s => s !== 'PHP'));    // remove
```
React compares with `Object.is`; mutating the same object keeps the same reference, so React skips the re-render.

## Lazy initial state
```jsx
const [draft, setDraft] = useState(() => JSON.parse(localStorage.getItem('draft') ?? '{}'));
```
The function runs only on the first render, so expensive work is not repeated.

## Summary
`useState` returns the current value and a setter; updates are batched, use functional updates for derived values, and never mutate objects or arrays.
MD,

'How does useEffect work?' => <<<'MD'
## What it is
`useEffect` runs **side effects** after React has updated the screen: fetching data, subscriptions, timers, analytics, manually touching the DOM. Rendering must stay pure; effects are where you talk to the outside world.

```jsx
useEffect(() => {
  // effect
  return () => { /* cleanup */ };
}, [dependencies]);
```

## When it runs
| Dependency array | Runs |
|---|---|
| none | after every render |
| `[]` | once after the first render (mount); cleanup on unmount |
| `[jobId]` | after the first render and whenever `jobId` changes |

Before re-running, React calls the previous cleanup. In development with `<StrictMode>` React mounts, unmounts and mounts again to expose missing cleanups.

## Fetching data correctly
```jsx
function JobDetail({ jobId }) {
  const [job, setJob] = useState(null);
  const [error, setError] = useState(null);

  useEffect(() => {
    const controller = new AbortController();
    fetch(`/api/jobs/${jobId}`, { signal: controller.signal })
      .then(r => { if (!r.ok) throw new Error(r.status); return r.json(); })
      .then(setJob)
      .catch(e => { if (e.name !== 'AbortError') setError(e); });
    return () => controller.abort();     // cancels the old request when jobId changes
  }, [jobId]);

  if (error) return <p>Could not load job</p>;
  return job ? <h1>{job.title}</h1> : <p>Loading…</p>;
}
```
Without the abort, a slow old response could overwrite the newer one (a race condition).

## Subscriptions and timers
```jsx
useEffect(() => {
  const id = setInterval(() => setSeconds(s => s + 1), 1000);
  return () => clearInterval(id);
}, []);
```

## When you do not need an effect
- Deriving data from props/state: compute it during render.
- Responding to a user event: do the work in the event handler.
- Server data in larger apps: use TanStack Query or SWR (caching, retries, deduplication).

## useLayoutEffect
Runs synchronously after DOM changes but before the browser paints; use it only to measure layout (tooltip position) to avoid flicker.

## Summary
`useEffect` synchronises a component with external systems after render; declare every dependency, and always clean up subscriptions, timers and in-flight requests.
MD,

'What are effect dependencies?' => <<<'MD'
## What they are
The dependency array tells React **which values the effect reads**. After each render React compares every dependency with its previous value using `Object.is`; if any changed, the old cleanup runs and the effect runs again.

```jsx
useEffect(() => {
  fetchJobs({ city, page });
}, [city, page]);   // re-fetch when city or page changes
```

## Rule: list everything the effect uses
Every prop, state variable and function defined in the component that the effect reads must be in the array. The ESLint rule `react-hooks/exhaustive-deps` checks this. Leaving one out creates a **stale closure**: the effect keeps using an old value.

```jsx
// Bug: always logs the first query
useEffect(() => {
  const id = setInterval(() => console.log(query), 1000);
  return () => clearInterval(id);
}, []);
```

## Objects and functions as dependencies
Objects and functions are recreated on every render, so they are "different" each time and the effect runs endlessly.
```jsx
const filters = { city, remote };          // new object every render
useEffect(() => { load(filters); }, [filters]);   // runs after every render
```
Fixes:
1. Depend on primitives: `[city, remote]`.
2. Move the object or function **inside** the effect.
3. Memoise: `const filters = useMemo(() => ({ city, remote }), [city, remote]);`
4. Wrap callbacks in `useCallback`.

## Avoiding a dependency legitimately
- Use a functional update: `setCount(c => c + 1)` does not need `count`.
- Use `useRef` for values you read but that should not re-trigger the effect.
- React 19's `useEffectEvent` separates "reactive" from "non-reactive" logic.

## Infinite loop example
```jsx
useEffect(() => { setJobs(data); }, [jobs]);   // setJobs changes jobs → effect runs again
```

## Summary
Dependencies are the effect's inputs. Include every reactive value it reads; keep them primitive or memoised so the effect runs exactly when its inputs change.
MD,

'When should useMemo and useCallback be used?' => <<<'MD'
## What they do
- `useMemo(fn, deps)` caches the **result** of a calculation until a dependency changes.
- `useCallback(fn, deps)` caches the **function itself** (same reference) until a dependency changes. It equals `useMemo(() => fn, deps)`.

```jsx
const visibleJobs = useMemo(
  () => jobs.filter(j => j.salary >= minSalary).sort(bySalary),
  [jobs, minSalary]
);

const handleApply = useCallback(id => apply(id, userId), [userId]);
```

## When they help
1. **Expensive calculations:** filtering or sorting thousands of rows, building chart data. Measure first: if it takes under ~1 ms, memoising adds overhead without benefit.
2. **Keeping references stable for memoised children:**
```jsx
const JobRow = React.memo(function JobRow({ job, onApply }) { ... });
// Without useCallback, onApply is a new function every render, so every JobRow re-renders.
<JobRow job={job} onApply={handleApply} />
```
3. **Values used as effect dependencies:** a memoised object or callback stops an effect from running after every render.
4. **Context values:** `value={useMemo(() => ({ user, logout }), [user])}` prevents all consumers re-rendering.

## When not to use them
- For cheap calculations (`a + b`, a small `map`).
- On every function "just in case": it makes code harder to read and costs memory and comparisons.
- To fix bugs: they are a performance hint, not a guarantee; React may discard the cache.

## React Compiler
React 19's compiler can memoise automatically, so in new projects manual `useMemo`/`useCallback` is needed less.

## Summary
Use `useMemo` for expensive derived values and `useCallback` for functions passed to memoised children or used as dependencies; profile before adding them elsewhere.
MD,

'How do you create a custom hook?' => <<<'MD'
## What it is
A custom hook is a function whose name starts with `use` and which calls other hooks. It lets several components **share stateful logic** (not state itself: each component calling the hook gets its own copy).

## Example: fetching data
```jsx
function useFetch(url) {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const controller = new AbortController();
    setLoading(true);
    fetch(url, { signal: controller.signal })
      .then(r => { if (!r.ok) throw new Error(`HTTP ${r.status}`); return r.json(); })
      .then(d => { setData(d); setError(null); })
      .catch(e => { if (e.name !== 'AbortError') setError(e); })
      .finally(() => setLoading(false));
    return () => controller.abort();
  }, [url]);

  return { data, loading, error };
}

function JobsPage() {
  const { data, loading, error } = useFetch('/api/jobs?city=Pune');
  if (loading) return <Spinner />;
  if (error) return <p>{error.message}</p>;
  return <JobList jobs={data} />;
}
```

## Example: debounced search input
```jsx
function useDebounce(value, delay = 400) {
  const [debounced, setDebounced] = useState(value);
  useEffect(() => {
    const id = setTimeout(() => setDebounced(value), delay);
    return () => clearTimeout(id);
  }, [value, delay]);
  return debounced;
}

const [query, setQuery] = useState('');
const debouncedQuery = useDebounce(query);
const { data } = useFetch(`/api/jobs?q=${encodeURIComponent(debouncedQuery)}`);
```
The API is called only after the user stops typing for 400 ms.

## Other useful custom hooks
- `useLocalStorage(key, initial)`: state that persists in the browser.
- `useAuth()`: reads the user and login/logout functions from context.
- `useOnClickOutside(ref, handler)`: closes dropdowns.

## Rules
- Name must start with `use` so the linter applies the rules of hooks.
- Call hooks unconditionally at the top level inside it.
- Return what callers need: a value, a tuple `[value, setValue]` or an object.

## Summary
Custom hooks extract reusable stateful logic into a `useX` function; each caller gets independent state, and components stay short and readable.
MD,

];

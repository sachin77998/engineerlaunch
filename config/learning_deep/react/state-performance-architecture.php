<?php

// React › State management, Performance and testing, Frontend architecture, in depth.
return [

'What is lifting state up?' => <<<'MD'
## What it is
When two or more components need the same changing data, you move ("lift") that state to their **closest common parent**. The parent holds the state and passes the value and a setter (or callback) down as props. There is then a single source of truth.

## Example: filter panel and result list
```jsx
function JobSearchPage() {
  const [filters, setFilters] = useState({ city: '', remote: false });
  return (
    <div className="layout">
      <FilterPanel filters={filters} onChange={setFilters} />
      <JobResults filters={filters} />
    </div>
  );
}

function FilterPanel({ filters, onChange }) {
  return (
    <label>
      <input type="checkbox" checked={filters.remote}
             onChange={e => onChange({ ...filters, remote: e.target.checked })} />
      Remote only
    </label>
  );
}
```
Before lifting, `FilterPanel` and `JobResults` each had their own copy and drifted out of sync. After lifting, both read the same object.

## How to decide where state lives
1. List every component that reads or changes the value.
2. Find their nearest common ancestor.
3. Put the state there (or higher only if needed).

## When lifting is not enough
If the common parent is many levels up and intermediate components only pass props along (**prop drilling**), consider:
- **Composition:** pass components as `children` so the middle layers do not need the data.
- **Context:** for app-wide values (current user, theme, language).
- **A store** (Zustand, Redux Toolkit) for large, frequently changing shared state.
- **URL state:** filters and page numbers belong in the query string (`?city=Pune&page=2`) so they survive refresh and can be shared.

## Summary
Lift shared state to the closest common parent and pass it down; this keeps one source of truth and predictable one-way data flow.
MD,

'When should Context be used?' => <<<'MD'
## What it is
Context passes a value through the component tree without threading props through every level.
```jsx
const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const value = useMemo(() => ({
    user,
    login: async (email, pwd) => setUser(await api.login(email, pwd)),
    logout: () => { api.logout(); setUser(null); },
  }), [user]);
  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export const useAuth = () => {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used inside AuthProvider');
  return ctx;
};

// Anywhere below the provider:
const { user, logout } = useAuth();
```

## Good uses
- Logged-in user and permissions
- Theme (light/dark), language, currency
- Feature flags, configuration
- Dependencies such as an API client

These change **rarely** and are read **widely**.

## Poor uses
- Fast-changing data (text input, mouse position, a large list being edited). Every consumer re-renders when the context value changes, even if it uses only part of it.
- Server data: use TanStack Query or SWR, which add caching and refetching.
- Replacing all props: explicit props make components easier to understand and reuse.

## Performance tips
- Memoise the `value` object (`useMemo`), otherwise it is new on every render.
- Split contexts: `UserContext` and `ThemeContext` separately, or separate state and dispatch contexts.
- For large shared state with selectors use Zustand or Redux Toolkit.

## Summary
Use Context for global, rarely changing values such as auth, theme and locale; avoid it for high-frequency updates and server cache.
MD,

'Controlled versus uncontrolled components?' => <<<'MD'
## Controlled components
The form value lives in React state; the input shows that state and every keystroke updates it.
```jsx
function EmailField() {
  const [email, setEmail] = useState('');
  const invalid = email && !email.includes('@');
  return (
    <>
      <input value={email} onChange={e => setEmail(e.target.value)} />
      {invalid && <small>Enter a valid email</small>}
    </>
  );
}
```
Advantages: instant validation, formatting (e.g. uppercase PAN), disabling the submit button, conditional fields.

## Uncontrolled components
The DOM keeps the value; React reads it only when needed through a ref or the form data.
```jsx
function ResumeUpload() {
  const fileRef = useRef(null);
  const submit = e => {
    e.preventDefault();
    const data = new FormData(e.target);          // or fileRef.current.files[0]
    upload(data);
  };
  return (
    <form onSubmit={submit}>
      <input name="fullName" defaultValue="" />
      <input type="file" name="resume" ref={fileRef} />
      <button>Upload</button>
    </form>
  );
}
```
Use `defaultValue` (not `value`) for the initial value. File inputs are always uncontrolled.

## Comparison
| | Controlled | Uncontrolled |
|---|---|---|
| Source of truth | React state | DOM |
| Re-render per keystroke | yes | no |
| Live validation | easy | on submit |
| Code | more | less |

## In practice
Form libraries such as **React Hook Form** use uncontrolled inputs with refs for performance, while still offering validation (often with Zod or Yup). A common warning, "A component is changing an uncontrolled input to be controlled", appears when `value` starts as `undefined`; initialise with `''`.

## Summary
Controlled inputs are driven by state (more control); uncontrolled inputs keep their own value and are read on demand (less code, fewer renders).
MD,

'How do reducers manage complex state?' => <<<'MD'
## What a reducer is
A reducer is a pure function `(state, action) => newState`. With `useReducer` all state transitions are described in one place, which is clearer than many `useState` calls changing related values.

```jsx
const initial = { items: [], status: 'idle', error: null, page: 1 };

function jobsReducer(state, action) {
  switch (action.type) {
    case 'fetch_start':   return { ...state, status: 'loading', error: null };
    case 'fetch_success': return { ...state, status: 'done', items: action.items };
    case 'fetch_error':   return { ...state, status: 'error', error: action.error };
    case 'next_page':     return { ...state, page: state.page + 1 };
    case 'remove':        return { ...state, items: state.items.filter(j => j.id !== action.id) };
    default: throw new Error(`Unknown action ${action.type}`);
  }
}

function Jobs() {
  const [state, dispatch] = useReducer(jobsReducer, initial);
  useEffect(() => {
    dispatch({ type: 'fetch_start' });
    api.jobs(state.page)
      .then(items => dispatch({ type: 'fetch_success', items }))
      .catch(error => dispatch({ type: 'fetch_error', error }));
  }, [state.page]);
  ...
}
```

## Why it helps
- **Impossible states disappear:** loading, error and data change together in one transition.
- **Testable:** a reducer is a plain function; test it without rendering.
- **Readable:** event handlers say *what happened* (`dispatch({type:'remove', id})`), the reducer says *how state changes*.
- `dispatch` has a stable identity, so it is safe to pass down or use in effects.

## useState or useReducer?
| Use `useState` | Use `useReducer` |
|---|---|
| independent simple values | several values that change together |
| few update paths | many actions (cart, multi-step form, wizard) |

## Scaling up
`useReducer` + Context gives a small global store. **Redux Toolkit** uses the same reducer idea with `createSlice`, Immer (write "mutating" code safely) and DevTools time-travel debugging.

## Summary
Reducers centralise state transitions in a pure function; use them when state is complex or several values must change together.
MD,

'How do server-state libraries differ from global stores?' => <<<'MD'
## Two kinds of state
- **Client state:** owned by the browser: open modal, selected tab, form draft, theme.
- **Server state:** owned by the backend: jobs, user profile, applications. It can change without you knowing, needs fetching, caching, refetching and can be stale.

Global stores (Redux, Zustand, Context) are designed for client state. Server-state libraries (**TanStack Query**, **SWR**, RTK Query, Apollo for GraphQL) are designed for server state.

## What a server-state library gives you
```jsx
const { data: jobs, isLoading, error } = useQuery({
  queryKey: ['jobs', { city, page }],
  queryFn: () => api.jobs({ city, page }),
  staleTime: 60_000,
});

const queryClient = useQueryClient();
const applyMutation = useMutation({
  mutationFn: jobId => api.apply(jobId),
  onSuccess: () => queryClient.invalidateQueries({ queryKey: ['applications'] }),
});
```
- **Caching by key:** two components asking for the same jobs share one request.
- **Deduplication** of simultaneous requests.
- **Background refetch** on window focus or reconnect; stale-while-revalidate.
- **Retries** with backoff, pagination and infinite scroll helpers.
- **Mutations** with cache invalidation or optimistic updates and rollback.
- Loading and error states without writing reducers.

## With only a global store
You would write actions for start/success/failure, keep `loading` flags, decide when data is stale, avoid duplicate requests and refresh manually: a lot of repetitive code that is easy to get wrong.

## Recommended split
| Data | Tool |
|---|---|
| API data | TanStack Query / SWR |
| URL-shareable filters | router search params |
| Small app-wide client state | Context or Zustand |
| Large complex client state | Redux Toolkit |
| Local UI state | `useState` |

## Summary
Server-state libraries manage remote data (cache, sync, refetch, mutations); global stores manage client-owned state. Most apps need both, used for their own purpose.
MD,

'Why does a component re-render?' => <<<'MD'
## The triggers
A component re-renders when:
1. **Its state changes** (`setState` with a different value).
2. **Its parent re-renders** (by default every child re-renders too, even with the same props).
3. **A context it consumes changes value.**
4. **A custom hook it uses** changes its internal state.

Props changing is not a separate trigger: props only change because the parent re-rendered.

Re-rendering means "React calls the function again and diffs the result". It does not necessarily change the DOM. Re-renders are usually cheap; they become a problem only when a large tree or expensive calculation re-runs often.

## Example
```jsx
function Page() {
  const [query, setQuery] = useState('');
  return (
    <>
      <input value={query} onChange={e => setQuery(e.target.value)} />
      <ExpensiveChart data={chartData} />   {/* re-renders on every keystroke */}
    </>
  );
}
```

## Fixes
- **Move state down:** put `query` and the input in their own `<SearchBox>` so `Page` does not re-render.
- **Pass content as children:** components given as `children` from above are not re-created by the state owner.
- **`React.memo`** on `ExpensiveChart`, with stable props (`useMemo`/`useCallback`).
- **Split contexts** or use selector-based stores so consumers only re-render for the slice they use.
- **`useDeferredValue` / `startTransition`** to keep typing responsive while a heavy list updates.

## Finding unnecessary re-renders
- React DevTools Profiler: "Highlight updates when components render" and "Why did this render?".
- `console.count('JobRow render')` during development.

## Summary
State changes, parent renders and context changes cause re-renders; keep state close to where it is used, and memoise only where profiling shows real cost.
MD,

'How does React.memo work?' => <<<'MD'
## What it does
`React.memo(Component)` returns a memoised component that **skips re-rendering when its props are shallowly equal** to the previous props.

```jsx
const JobRow = React.memo(function JobRow({ job, onApply }) {
  console.log('render', job.id);
  return <li>{job.title} <button onClick={() => onApply(job.id)}>Apply</button></li>;
});
```

## Shallow comparison
Each prop is compared with `Object.is`:
- Primitives (`'abc'`, `5`, `true`) compare by value.
- Objects, arrays and functions compare by **reference**. A new object or arrow function each render breaks memo.

```jsx
function JobList({ jobs }) {
  const [selected, setSelected] = useState(null);

  // Breaks memo: new function every render
  // <JobRow onApply={id => apply(id)} />

  const handleApply = useCallback(id => apply(id), []);
  return jobs.map(j => <JobRow key={j.id} job={j} onApply={handleApply} />);
}
```
Now selecting an item re-renders `JobList`, but unchanged `JobRow`s skip rendering.

## Custom comparison
```jsx
export default React.memo(JobRow, (prev, next) => prev.job.updatedAt === next.job.updatedAt);
```
Return `true` to skip the render. Use carefully: forgetting a prop causes stale UI.

## What memo does not stop
- Re-renders from the component's **own** state or a context it uses.
- Changes in `children` (JSX children are new objects every time).

## When to use it
- Rows of long lists, charts, editors: expensive to render and often given the same props.
- Not for small, cheap components: comparing props then costs more than rendering.

## Summary
`React.memo` skips a re-render when props are shallowly equal; it works only if objects and callbacks passed in are kept stable with `useMemo` and `useCallback`.
MD,

'What is code splitting?' => <<<'MD'
## What it is
Code splitting breaks your JavaScript bundle into smaller chunks that load **on demand**, so the first page downloads only the code it needs. A single 3 MB bundle slows the first load on mobile networks; splitting by route often cuts it to a few hundred KB.

## Route-based splitting with React.lazy
```jsx
import { lazy, Suspense } from 'react';
const AdminDashboard = lazy(() => import('./pages/AdminDashboard'));
const ResumeBuilder  = lazy(() => import('./pages/ResumeBuilder'));

<Suspense fallback={<Spinner />}>
  <Routes>
    <Route path="/admin" element={<AdminDashboard />} />
    <Route path="/resume" element={<ResumeBuilder />} />
  </Routes>
</Suspense>
```
The bundler (Vite, webpack) creates a separate file for each `import()`. It is downloaded the first time the route renders; `Suspense` shows the fallback meanwhile.

## Component-level splitting
Load heavy widgets only when needed:
```jsx
const PdfPreview = lazy(() => import('./PdfPreview'));   // pdf.js is large
{showPreview && <Suspense fallback="Loading preview…"><PdfPreview file={file} /></Suspense>}
```

## Library-level splitting
```js
button.onclick = async () => {
  const { utils, writeFile } = await import('xlsx');   // only when exporting
  writeFile(utils.book_new(), 'jobs.xlsx');
};
```

## Good practices
- Split by route first; that gives the biggest win.
- **Prefetch** likely next routes on hover (`import('./pages/Job')` in `onMouseEnter`).
- Analyse bundles with `vite-bundle-visualizer` or `webpack-bundle-analyzer`; replace heavy libraries (moment.js → date-fns or dayjs).
- Wrap lazy routes in an **error boundary** to handle chunk load failures after a deployment.

## Summary
Code splitting with dynamic `import()`, `React.lazy` and `Suspense` loads code only when needed, reducing initial load time.
MD,

'How do you test user behavior?' => <<<'MD'
## Principle
Test what the user sees and does, not implementation details. **React Testing Library (RTL)** encourages this: find elements by role, label or text, interact like a user, and assert on visible results. It runs with **Vitest** or **Jest**.

## Example
```jsx
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import LoginForm from './LoginForm';

test('shows an error for a wrong password', async () => {
  const user = userEvent.setup();
  const onLogin = vi.fn().mockRejectedValue(new Error('Invalid credentials'));
  render(<LoginForm onLogin={onLogin} />);

  await user.type(screen.getByLabelText(/email/i), 'asha@example.com');
  await user.type(screen.getByLabelText(/password/i), 'wrong');
  await user.click(screen.getByRole('button', { name: /sign in/i }));

  expect(onLogin).toHaveBeenCalledWith('asha@example.com', 'wrong');
  expect(await screen.findByText(/invalid credentials/i)).toBeInTheDocument();
});
```

## Queries, in order of preference
1. `getByRole('button', { name: 'Apply' })`: also checks accessibility.
2. `getByLabelText`, `getByPlaceholderText`, `getByText`.
3. `getByTestId` only as a last resort.

`getBy…` throws if missing, `queryBy…` returns null (assert absence), `findBy…` waits for async elements.

## Mocking the network
Use **MSW (Mock Service Worker)** to intercept `fetch` at the network level, so components run their real data-fetching code:
```js
http.get('/api/jobs', () => HttpResponse.json([{ id: 1, title: 'React Developer' }]))
```

## Test pyramid for a React app
- **Unit:** pure functions, reducers, hooks (`renderHook`).
- **Component/integration:** RTL tests of forms, lists, flows with MSW.
- **End-to-end:** **Playwright** or Cypress in a real browser for critical journeys (sign up, apply to a job, payment).

## What not to test
Internal state values, private functions, CSS class names, or that `useState` was called.

## Summary
Render the component, act like a user with `userEvent`, find elements by role or label, mock the network with MSW, and assert on what the user sees.
MD,

'How do you diagnose a slow React page?' => <<<'MD'
## Step 1: measure, do not guess
- **Lighthouse / PageSpeed Insights:** LCP (largest contentful paint), INP (interaction to next paint), CLS (layout shift), bundle size.
- **Chrome DevTools Performance tab:** record the slow interaction; look for long tasks (> 50 ms) and what JavaScript runs.
- **React DevTools Profiler:** which components rendered, how long, and why.
- **Network tab:** slow or waterfall API calls, large images, too many requests.

## Step 2: identify the type of slowness
| Symptom | Likely cause | Fix |
|---|---|---|
| Slow first load | big bundle, render-blocking scripts | code splitting, tree shaking, lighter libraries, SSR |
| Spinner for long | slow or sequential API calls | parallel requests, caching (TanStack Query), backend indexing, pagination |
| Typing lags | big tree re-renders per keystroke | move state down, `React.memo`, `useDeferredValue`, debounce |
| Scrolling a long list is janky | thousands of DOM nodes | virtualisation (`react-window`, TanStack Virtual) |
| Layout jumps | images without dimensions, late fonts | set width/height, `font-display: swap`, skeletons |
| Gets slower over time | memory leak | clean up effects, listeners, intervals |

## Step 3: common fixes in code
```jsx
// Virtualise a 10,000-row list
import { FixedSizeList } from 'react-window';
<FixedSizeList height={600} itemCount={jobs.length} itemSize={72} width="100%">
  {({ index, style }) => <div style={style}><JobRow job={jobs[index]} /></div>}
</FixedSizeList>

// Keep input responsive while filtering
const deferredQuery = useDeferredValue(query);
const results = useMemo(() => search(jobs, deferredQuery), [jobs, deferredQuery]);
```
- Images: modern formats (WebP/AVIF), `loading="lazy"`, correct sizes, a CDN.
- Avoid request waterfalls: start fetches in parallel (`Promise.all`) or at the route level.

## Step 4: verify
Re-run the profiler and Lighthouse after each change and compare numbers. Add a performance budget in CI (bundle size limit) so it does not regress.

## Summary
Measure with Lighthouse, the Performance tab and the React Profiler; classify the problem (load, network, render, list size, memory); apply the targeted fix and measure again.
MD,

'How should a React project be structured?' => <<<'MD'
## Feature-based structure (recommended for medium and large apps)
```text
src/
  app/                 # app shell: providers, router, global styles
    App.jsx
    routes.jsx
  features/
    jobs/
      api.js           # fetch functions / query hooks
      components/      # JobCard.jsx, JobFilters.jsx
      hooks/           # useJobs.js
      pages/           # JobsPage.jsx, JobDetailPage.jsx
      jobs.test.jsx
    auth/
    resume/
  components/          # shared UI: Button, Modal, Input, Table
  hooks/               # shared hooks: useDebounce, useLocalStorage
  lib/                 # api client (axios instance), formatters, constants
  assets/
```
Everything for one feature lives together, so a developer working on "jobs" rarely leaves that folder. Shared pieces are promoted to `components/` or `lib/` only when a second feature needs them.

## Small projects
A flat `components/`, `pages/`, `hooks/`, `services/` layout is fine; switch to feature folders when it grows.

## Conventions that help teams
- One component per file, `PascalCase.jsx`; hooks `useCamelCase.js`.
- An `index.js` per feature exporting its public API only.
- A single API client with base URL, auth header and error handling:
```js
export const api = axios.create({ baseURL: import.meta.env.VITE_API_URL, withCredentials: true });
api.interceptors.response.use(r => r, err => {
  if (err.response?.status === 401) window.location.assign('/login');
  return Promise.reject(err);
});
```
- Environment variables in `.env` (`VITE_` prefix in Vite); never put secrets in frontend code.
- Absolute imports (`@/features/jobs`) via path aliases.
- ESLint + Prettier + TypeScript for consistency and type safety.

## Separation of concerns
- Pages: compose features and read route params.
- Feature components: UI + calls to hooks.
- Hooks: state and data logic.
- `lib`/`api`: network and pure utilities.

## Summary
Organise by feature, keep shared UI and utilities separate, centralise the API client, and enforce conventions with linting and TypeScript.
MD,

'How do error boundaries work?' => <<<'MD'
## What they are
An error boundary is a component that **catches JavaScript errors during rendering** of its children, logs them and shows a fallback UI instead of crashing the whole app (without one, React unmounts the entire tree and the user sees a blank page).

They are still written as **class components**, because they rely on lifecycle methods:
```jsx
class ErrorBoundary extends React.Component {
  state = { hasError: false };

  static getDerivedStateFromError() {
    return { hasError: true };                 // render the fallback next
  }

  componentDidCatch(error, info) {
    logToSentry(error, info.componentStack);   // report the error
  }

  render() {
    if (this.state.hasError) {
      return this.props.fallback ?? <p>Something went wrong. <button onClick={() => this.setState({ hasError: false })}>Try again</button></p>;
    }
    return this.props.children;
  }
}

<ErrorBoundary fallback={<p>Could not load the chart</p>}>
  <SalaryChart />
</ErrorBoundary>
```
The `react-error-boundary` package gives a ready-made function-friendly version with `resetKeys` and `onReset`.

## What they catch and do not catch
| Caught | Not caught |
|---|---|
| errors in render | errors in event handlers (use try/catch) |
| errors in lifecycle methods and effects of children | async code such as `setTimeout`, promises |
| errors in constructors of children | errors in the boundary itself |
| | server-side rendering errors |

For async errors in handlers, catch them and put them in state, or rethrow during render so the boundary handles them.

## Where to place them
- One at the top level (whole-app fallback with a reload button).
- Around each route, so one broken page does not break navigation.
- Around risky widgets (third-party embeds, charts, rich text editors).
- Around `lazy` components to handle failed chunk downloads after a deployment.

## Summary
Error boundaries are class components using `getDerivedStateFromError` and `componentDidCatch` to catch render errors in their subtree and show a fallback; handle event and async errors separately.
MD,

'How do you secure API calls?' => <<<'MD'
## Ground rule
**Anything in the browser is public.** Users can read your JavaScript, change requests and call the API directly. Security must be enforced on the **server**; the frontend only avoids leaking data and makes attacks harder.

## Authentication tokens
| Option | XSS risk | CSRF risk | Notes |
|---|---|---|---|
| HttpOnly, Secure, SameSite cookie | token not readable by JS | needs SameSite or CSRF token | recommended (Laravel Sanctum SPA mode) |
| `localStorage` bearer token | any injected script can steal it | none | simple but riskier |
| in-memory access token + HttpOnly refresh cookie | low | low | good for JWT setups |

```js
// Cookie-based session (Laravel Sanctum)
await axios.get('/sanctum/csrf-cookie');
await axios.post('/login', { email, password }, { withCredentials: true });
```

## Protect against XSS
- React escapes output; avoid `dangerouslySetInnerHTML`, or sanitise with DOMPurify.
- Never build URLs from user input without validation (`javascript:` links).
- Set a **Content Security Policy** header.

## CSRF
With cookies, use `SameSite=Lax/Strict` and the framework's CSRF token (the `X-XSRF-TOKEN` header that axios sends automatically).

## CORS
Configure the API to allow only your frontend origin; CORS is a browser rule, not a security wall against direct calls.

## Never put secrets in the frontend
Payment gateway secret keys, database URLs, third-party API secrets must stay on the server. `VITE_`/`REACT_APP_` variables are compiled into the bundle and visible to everyone. Call such services through your own backend.

## Server-side checks the frontend relies on
- Authorise every request (`can this user see application 42?`), not only hide buttons.
- Validate all input; rate-limit logins and OTPs; use HTTPS everywhere.

## Handling expiry
An axios interceptor can refresh the token on 401 once, then redirect to login if it fails.

## Summary
Use HttpOnly cookies or short-lived in-memory tokens, guard against XSS and CSRF, keep secrets on the server, and enforce authorisation on the API, because the client can never be trusted.
MD,

'What is hydration?' => <<<'MD'
## What it is
With server-side rendering (SSR), the server sends ready HTML so the page appears quickly and is crawlable by search engines. That HTML is not interactive yet. **Hydration** is the step where React loads in the browser, renders the same component tree, attaches event listeners to the existing HTML and takes over, without recreating the DOM.

```jsx
// client entry
import { hydrateRoot } from 'react-dom/client';
hydrateRoot(document.getElementById('root'), <App />);
```
(Compare with `createRoot(...).render(<App/>)` for client-only apps, which builds the DOM from scratch.)

## Hydration mismatch
React expects the first client render to produce exactly the server's HTML. If not, it warns and may re-render the whole subtree. Common causes:
- `Date.now()`, `Math.random()`, or current time in render.
- Reading `window`, `localStorage` or screen size during render (undefined on the server).
- Locale differences in number or date formatting.
- Invalid HTML nesting (`<div>` inside `<p>`).

Fix: render browser-only values after mount:
```jsx
const [mounted, setMounted] = useState(false);
useEffect(() => setMounted(true), []);
return <span>{mounted ? new Date().toLocaleTimeString() : ''}</span>;
```

## Cost of hydration
The browser still downloads and runs all component JavaScript, so a page can look ready but not respond to clicks (poor INP). Modern improvements:
- **Selective/streaming hydration (React 18):** parts wrapped in `Suspense` hydrate independently, and parts the user interacts with are prioritised.
- **React Server Components (Next.js App Router):** server-only components send no JavaScript, so less to hydrate.
- **Islands architecture (Astro):** only interactive widgets are hydrated.

## Summary
Hydration attaches React's interactivity to server-rendered HTML; the client render must match the server's output, and less client JavaScript means faster hydration.
MD,

'CSR versus SSR versus SSG?' => <<<'MD'
## The three rendering strategies
| | CSR (client-side rendering) | SSR (server-side rendering) | SSG (static site generation) |
|---|---|---|---|
| HTML built | in the browser | on the server per request | at build time |
| First paint | slower (blank until JS loads) | fast | fastest (served from CDN) |
| SEO | weaker | strong | strong |
| Data freshness | live | live per request | as of the last build |
| Server cost | low (static files) | higher (render each request) | lowest |
| Example | admin dashboard, logged-in app | job search results, product pages with live prices | blog, docs, marketing pages |

## CSR
Vite + React builds a bundle; the server sends an almost empty `index.html`, and JavaScript fetches data and renders. Great for apps behind login where SEO does not matter.

## SSR
The server (Next.js, Remix) runs React for each request, sends complete HTML, then the browser hydrates it.
```js
// Next.js App Router: a server component fetching per request
export default async function JobsPage({ searchParams }) {
  const jobs = await fetch(`${API}/jobs?city=${searchParams.city}`, { cache: 'no-store' }).then(r => r.json());
  return <JobList jobs={jobs} />;
}
```

## SSG and ISR
Pages are generated once at build time and served as static files. **Incremental Static Regeneration (ISR)** regenerates a page in the background after a set time:
```js
fetch(`${API}/companies/${slug}`, { next: { revalidate: 3600 } });   // refresh hourly
```
Good for company pages or articles that change occasionally but need SEO and speed.

## Choosing
- Public pages that must rank on Google (jobs, companies): SSR or SSG/ISR.
- Content that rarely changes: SSG.
- Private, highly interactive dashboards: CSR.
- Real apps mix them: Next.js lets each route choose.

## Summary
CSR renders in the browser, SSR on the server per request, SSG at build time; choose per page based on SEO needs, data freshness and server cost.
MD,

];

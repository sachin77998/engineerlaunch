{{-- Calculator formulas. CALCS[slug] = {inputs: [...], compute(values) => {donut, rows, chart, table}}. Amounts in ₹. --}}
<script>
window.CALCS = (() => {
    const inr = (n, d = 0) => (n < 0 ? '−' : '') + '₹' + Math.abs(n).toLocaleString('en-IN', {maximumFractionDigits: d, minimumFractionDigits: d});
    const pct = (n, d = 2) => (isFinite(n) ? n.toFixed(d) : '—') + '%';
    const num = (n, d = 0) => Number(n).toLocaleString('en-IN', {maximumFractionDigits: d});
    const yr = n => n + (n === 1 ? ' year' : ' years');
    const C = {navy: '#0b2545', blue: '#527fad', light: '#9cb6d3', gold: '#f5b800', green: '#43936c', red: '#b42318', grey: '#94a3b8', orange: '#e8743b'};
    const money = (k, label, value, min, max, step = 1000) => ({k, label, value, min, max, step, prefix: '₹'});
    const rate = (k, label, value, min, max, step = 0.1) => ({k, label, value, min, max, step, suffix: '%'});
    const years = (k, label, value, min, max, step = 1) => ({k, label, value, min, max, step, suffix: 'Yr'});
    const select = (k, label, value, options) => ({k, label, value, options});

    // Equated monthly instalment.
    const emiOf = (p, annual, months) => { const r = annual / 1200; return r === 0 ? p / months : p * r * (1 + r) ** months / ((1 + r) ** months - 1); };
    // Month-by-month loan schedule with an optional lump-sum prepayment and extra monthly payment.
    const schedule = (p, annual, months, emi, lump = 0, lumpMonth = 0, extra = 0) => {
        const r = annual / 1200; let bal = p, m = 0, interest = 0; const years = [];
        let y = {principal: 0, interest: 0};
        while (bal > 0.5 && m < 1200) {
            m++;
            const i = bal * r; let pay = Math.min(emi + extra, bal + i); let prin = pay - i;
            if (m === lumpMonth && lump > 0) prin += Math.min(lump, bal - prin);
            bal -= prin; interest += i; y.principal += prin; y.interest += i;
            if (m % 12 === 0 || bal <= 0.5) { years.push({...y, balance: Math.max(bal, 0)}); y = {principal: 0, interest: 0}; }
        }
        return {months: m, interest, years};
    };
    const loan = (amount, maxAmount, rateValue, rateMax, tenure, tenureMax) => ({
        inputs: [money('p', 'Loan amount', amount, 10000, maxAmount, 10000), rate('r', 'Rate of interest (p.a.)', rateValue, 1, rateMax, 0.05), years('y', 'Loan tenure', tenure, 1, tenureMax)],
        compute: v => {
            const n = v.y * 12, emi = emiOf(v.p, v.r, n), s = schedule(v.p, v.r, n, emi);
            return {
                donut: {labels: ['Principal amount', 'Interest amount'], values: [v.p, s.interest]},
                rows: [['Monthly EMI', inr(emi)], ['Principal amount', inr(v.p)], ['Total interest', inr(s.interest)], ['Total amount', inr(v.p + s.interest)], ['Interest as % of principal', pct(s.interest / v.p * 100, 1)]],
                chart: {type: 'bar', title: 'Year-wise repayment', labels: s.years.map((_, i) => 'Year ' + (i + 1)), datasets: [
                    {label: 'Principal paid', data: s.years.map(y => Math.round(y.principal)), color: C.blue, stack: 'a'},
                    {label: 'Interest paid', data: s.years.map(y => Math.round(y.interest)), color: C.orange, stack: 'a'},
                    {label: 'Balance', data: s.years.map(y => Math.round(y.balance)), color: C.navy, type: 'line', axis: 'y2'}]},
                table: {head: ['Year', 'Principal paid', 'Interest paid', 'Total paid', 'Balance'], rows: s.years.map((y, i) => [i + 1, inr(y.principal), inr(y.interest), inr(y.principal + y.interest), inr(y.balance)])},
            };
        },
    });
    // Year-end balances of a deposit that adds `deposit(year, month)` each month and compounds at monthly rate i.
    const grow = (yearsCount, i, deposit, start = 0) => {
        let bal = start, invested = start; const rows = [];
        for (let y = 1; y <= yearsCount; y++) {
            for (let m = 1; m <= 12; m++) { const d = deposit(y, m); bal = (bal + d) * (1 + i); invested += d; }
            rows.push({year: y, invested, value: bal});
        }
        return rows;
    };
    const growthResult = (rows, labelInvested = 'Invested amount', extraRows = []) => {
        const last = rows[rows.length - 1] || {invested: 0, value: 0};
        return {
            donut: {labels: [labelInvested, 'Est. returns'], values: [last.invested, Math.max(last.value - last.invested, 0)]},
            rows: [[labelInvested, inr(last.invested)], ['Est. returns', inr(last.value - last.invested)], ['Total value', inr(last.value)], ...extraRows],
            chart: {type: 'bar', title: 'Growth year by year', labels: rows.map(r => 'Year ' + r.year), datasets: [
                {label: labelInvested, data: rows.map(r => Math.round(r.invested)), color: C.blue, stack: 'a'},
                {label: 'Returns', data: rows.map(r => Math.round(r.value - r.invested)), color: C.green, stack: 'a'}]},
            table: {head: ['Year', labelInvested, 'Returns', 'Value'], rows: rows.map(r => [r.year, inr(r.invested), inr(r.value - r.invested), inr(r.value)])},
        };
    };
    const metal = (unitLabel, perUnit, purities, gst, making) => ({
        inputs: [
            {k: 'w', label: 'Weight', value: unitLabel === 'kg' ? 1 : 10, min: unitLabel === 'kg' ? 0.1 : 1, max: unitLabel === 'kg' ? 1000 : 1000, step: unitLabel === 'kg' ? 0.1 : 0.5, suffix: unitLabel === 'kg' ? 'kg' : 'g'},
            ...(purities ? [select('purity', 'Purity', Object.keys(purities)[0], Object.keys(purities))] : []),
            money('price', `Price per ${window.METAL?.unit || perUnit}${purities ? ' (pure, ' + Object.keys(purities)[0].split(' ')[0] + ')' : ''}`, Math.round(window.METAL?.latest || 0), 1, Math.max(1000, Math.round((window.METAL?.latest || 1000) * 3)), 1),
            ...(making ? [rate('making', 'Making charges', making, 0, 35, 0.5)] : []),
            rate('gst', 'GST', gst, 0, 28, 0.5),
        ],
        compute: v => {
            const unit = window.METAL?.unit || perUnit;
            const grams = unitLabel === 'kg' ? v.w * 1000 : v.w;
            const perGram = unit === '10 gram' ? v.price / 10 : v.price / 1000;
            const value = grams * perGram * (purities ? purities[v.purity] : 1);
            const makingAmt = value * (v.making || 0) / 100, tax = (value + makingAmt) * v.gst / 100;
            return {
                donut: {labels: ['Metal value', 'Making charges', 'GST'], values: [value, makingAmt, tax]},
                rows: [['Metal value', inr(value)], ...(making ? [['Making charges', inr(makingAmt)]] : []), ['GST', inr(tax)], ['Total price', inr(value + makingAmt + tax)],
                    ['Price per gram (this purity)', inr(perGram * (purities ? purities[v.purity] : 1), 2)]],
            };
        },
    });

    return {
        gold: metal('g', '10 gram', {'24K (99.9%)': 1, '22K (91.6%)': 0.916, '18K (75%)': 0.75, '14K (58.5%)': 0.585}, 3, 8),
        silver: metal('g', 'kg', {'999 (fine)': 1, '925 (sterling)': 0.925, '900': 0.9, '800': 0.8}, 3, 6),
        copper: metal('kg', 'kg', null, 18, 0),

        'personal-loan-emi': loan(500000, 4000000, 12, 30, 5, 7),
        'home-loan-emi': loan(5000000, 100000000, 8.5, 15, 20, 30),
        'car-loan-emi': loan(800000, 10000000, 9, 20, 5, 8),
        'bike-loan-emi': loan(100000, 1000000, 11, 25, 3, 5),
        'home-loan-prepayment': {
            inputs: [money('p', 'Loan amount', 5000000, 100000, 100000000, 10000), rate('r', 'Rate of interest (p.a.)', 8.5, 1, 15, 0.05), years('y', 'Loan tenure', 20, 1, 30),
                money('lump', 'One-time prepayment', 500000, 0, 50000000, 10000), {k: 'at', label: 'Prepay after (months)', value: 24, min: 1, max: 360, step: 1, suffix: 'mo'},
                money('extra', 'Extra every month', 0, 0, 500000, 500)],
            compute: v => {
                const n = v.y * 12, emi = emiOf(v.p, v.r, n), base = schedule(v.p, v.r, n, emi), pre = schedule(v.p, v.r, n, emi, v.lump, v.at, v.extra);
                const saved = base.interest - pre.interest;
                return {
                    donut: {labels: ['Interest you pay', 'Interest saved'], values: [pre.interest, Math.max(saved, 0)]},
                    rows: [['Monthly EMI', inr(emi)], ['Interest without prepayment', inr(base.interest)], ['Interest with prepayment', inr(pre.interest)], ['Interest saved', inr(saved)],
                        ['Loan closes in', `${Math.floor(pre.months / 12)}y ${pre.months % 12}m (was ${v.y}y)`], ['Months saved', num(base.months - pre.months)]],
                    chart: {type: 'line', title: 'Outstanding balance', labels: base.years.map((_, i) => 'Year ' + (i + 1)), datasets: [
                        {label: 'Without prepayment', data: base.years.map(y => Math.round(y.balance)), color: C.grey},
                        {label: 'With prepayment', data: base.years.map((_, i) => Math.round(pre.years[i]?.balance ?? 0)), color: C.blue}]},
                };
            },
        },

        'mutual-fund': {inputs: [money('p', 'Total investment', 25000, 500, 10000000, 500), rate('r', 'Expected return rate (p.a.)', 12, 1, 30), years('y', 'Time period', 10, 1, 40)],
            compute: v => growthResult(Array.from({length: v.y}, (_, i) => ({year: i + 1, invested: v.p, value: v.p * (1 + v.r / 100) ** (i + 1)})))},
        lumpsum: {inputs: [money('p', 'Total investment', 100000, 500, 10000000, 500), rate('r', 'Expected return rate (p.a.)', 12, 1, 30), years('y', 'Time period', 10, 1, 40)],
            compute: v => growthResult(Array.from({length: v.y}, (_, i) => ({year: i + 1, invested: v.p, value: v.p * (1 + v.r / 100) ** (i + 1)})))},
        sip: {inputs: [money('m', 'Monthly investment', 10000, 100, 1000000, 100), rate('r', 'Expected return rate (p.a.)', 12, 1, 30), years('y', 'Time period', 10, 1, 40), rate('step', 'Yearly step-up', 0, 0, 50, 1)],
            compute: v => growthResult(grow(v.y, v.r / 1200, y => v.m * (1 + v.step / 100) ** (y - 1)))},
        swp: {
            inputs: [money('p', 'Total investment', 1000000, 10000, 50000000, 10000), money('w', 'Withdrawal per month', 10000, 500, 500000, 500), rate('r', 'Expected return rate (p.a.)', 8, 1, 30), years('y', 'Time period', 10, 1, 40)],
            compute: v => {
                const i = v.r / 1200; let bal = v.p, out = 0, lasted = 0; const rows = [];
                for (let y = 1; y <= v.y; y++) {
                    for (let m = 0; m < 12; m++) { if (bal <= 0) break; const w = Math.min(v.w, bal); bal = (bal - w) * (1 + i); out += w; lasted++; }
                    rows.push({year: y, withdrawn: out, value: Math.max(bal, 0)});
                }
                return {
                    donut: {labels: ['Total withdrawn', 'Final value'], values: [out, Math.max(bal, 0)]},
                    rows: [['Total investment', inr(v.p)], ['Total withdrawn', inr(out)], ['Final value', inr(Math.max(bal, 0))], ['Money lasts', lasted < v.y * 12 ? `${Math.floor(lasted / 12)}y ${lasted % 12}m` : 'Whole period']],
                    chart: {type: 'line', title: 'Balance after withdrawals', labels: rows.map(r => 'Year ' + r.year), datasets: [{label: 'Balance', data: rows.map(r => Math.round(r.value)), color: C.blue}, {label: 'Withdrawn so far', data: rows.map(r => Math.round(r.withdrawn)), color: C.orange}]},
                    table: {head: ['Year', 'Withdrawn so far', 'Balance'], rows: rows.map(r => [r.year, inr(r.withdrawn), inr(r.value)])},
                };
            },
        },
        cagr: {
            inputs: [money('a', 'Initial value', 100000, 1, 100000000, 1000), money('b', 'Final value', 250000, 1, 1000000000, 1000), years('y', 'Duration', 5, 1, 40)],
            compute: v => {
                const g = ((v.b / v.a) ** (1 / v.y) - 1) * 100;
                return {donut: {labels: ['Initial value', 'Growth'], values: [v.a, Math.max(v.b - v.a, 0)]},
                    rows: [['CAGR', pct(g)], ['Absolute return', pct((v.b / v.a - 1) * 100)], ['Gain', inr(v.b - v.a)]],
                    chart: {type: 'line', title: 'Value growing at the CAGR', labels: Array.from({length: v.y + 1}, (_, i) => 'Year ' + i), datasets: [{label: 'Value', data: Array.from({length: v.y + 1}, (_, i) => Math.round(v.a * (1 + g / 100) ** i)), color: C.green}]}};
            },
        },
        roi: {
            inputs: [money('a', 'Amount invested', 500000, 1, 100000000, 1000), money('b', 'Amount returned', 1000000, 0, 1000000000, 1000), years('y', 'Duration', 5, 1, 40)],
            compute: v => ({donut: {labels: ['Investment', 'Profit'], values: [v.a, Math.max(v.b - v.a, 0)]},
                rows: [['Investment gain', inr(v.b - v.a)], ['ROI', pct((v.b - v.a) / v.a * 100)], ['Annualised ROI', pct(((v.b / v.a) ** (1 / v.y) - 1) * 100)]]}),
        },
        npv: {
            inputs: [money('c0', 'Initial investment', 1000000, 1000, 1000000000, 1000), money('cf', 'Cash flow in year 1', 300000, 0, 500000000, 1000), rate('g', 'Cash flow growth per year', 5, -20, 50, 0.5),
                years('y', 'Number of years', 5, 1, 40), rate('d', 'Discount rate', 10, 1, 30)],
            compute: v => {
                const flows = Array.from({length: v.y}, (_, i) => v.cf * (1 + v.g / 100) ** i);
                const pvAt = r => flows.reduce((s, f, i) => s + f / (1 + r) ** (i + 1), 0);
                const pv = pvAt(v.d / 100), npv = pv - v.c0;
                let lo = -0.99, hi = 10; for (let k = 0; k < 100; k++) { const mid = (lo + hi) / 2; (pvAt(mid) - v.c0 > 0) ? lo = mid : hi = mid; }
                return {
                    donut: {labels: ['Initial investment', 'Present value gained'], values: [v.c0, Math.max(npv, 0)]},
                    rows: [['NPV', inr(npv)], ['Present value of cash flows', inr(pv)], ['Total cash flows', inr(flows.reduce((a, b) => a + b, 0))], ['IRR', pct(lo * 100)], ['Decision', npv >= 0 ? 'Accept: adds value' : 'Reject: destroys value']],
                    chart: {type: 'bar', title: 'Cash flow and its present value', labels: flows.map((_, i) => 'Year ' + (i + 1)), datasets: [{label: 'Cash flow', data: flows.map(Math.round), color: C.light}, {label: 'Present value', data: flows.map((f, i) => Math.round(f / (1 + v.d / 100) ** (i + 1))), color: C.blue}]},
                    table: {head: ['Year', 'Cash flow', 'Discount factor', 'Present value'], rows: flows.map((f, i) => [i + 1, inr(f), (1 / (1 + v.d / 100) ** (i + 1)).toFixed(4), inr(f / (1 + v.d / 100) ** (i + 1))])},
                };
            },
        },

        'fixed-deposit': {
            inputs: [money('p', 'Total investment', 100000, 1000, 100000000, 1000), rate('r', 'Rate of interest (p.a.)', 7, 1, 15, 0.05), years('y', 'Time period', 5, 1, 25),
                select('n', 'Compounding', 'Quarterly', ['Monthly', 'Quarterly', 'Half-yearly', 'Yearly'])],
            compute: v => { const n = {Monthly: 12, Quarterly: 4, 'Half-yearly': 2, Yearly: 1}[v.n];
                return growthResult(Array.from({length: v.y}, (_, i) => ({year: i + 1, invested: v.p, value: v.p * (1 + v.r / 100 / n) ** (n * (i + 1))})), 'Invested amount'); },
        },
        'recurring-deposit': {
            inputs: [money('m', 'Monthly investment', 5000, 100, 1000000, 100), rate('r', 'Rate of interest (p.a.)', 6.7, 1, 15, 0.05), years('y', 'Time period', 5, 1, 10)],
            // Quarterly compounding, as banks and the Post Office compute RD interest.
            compute: v => growthResult(grow(v.y, (1 + v.r / 400) ** (1 / 3) - 1, () => v.m)),
        },
        ppf: {
            inputs: [money('d', 'Yearly investment', 150000, 500, 150000, 500), rate('r', 'Rate of interest', 7.1, 1, 12, 0.05), {k: 'y', label: 'Time period', value: 15, min: 15, max: 50, step: 5, suffix: 'Yr'}],
            compute: v => { let bal = 0; const rows = []; for (let y = 1; y <= v.y; y++) { bal = (bal + v.d) * (1 + v.r / 100); rows.push({year: y, invested: v.d * y, value: bal}); } return growthResult(rows, 'Invested amount', [['Tax', 'EEE: deposits, interest and maturity tax-free']]); },
        },
        nsc: {
            inputs: [money('p', 'Amount invested', 100000, 1000, 10000000, 1000), rate('r', 'Rate of interest', 7.7, 1, 12, 0.05), years('y', 'Time period', 5, 5, 5)],
            compute: v => growthResult(Array.from({length: v.y}, (_, i) => ({year: i + 1, invested: v.p, value: v.p * (1 + v.r / 100) ** (i + 1)}))),
        },
        ssy: {
            inputs: [money('d', 'Yearly investment', 150000, 250, 150000, 250), {k: 'age', label: "Girl's age", value: 5, min: 0, max: 10, step: 1, suffix: 'Yr'}, rate('r', 'Rate of interest', 8.2, 1, 12, 0.05)],
            compute: v => {
                // Deposits for 15 years; the account matures 21 years after opening.
                let bal = 0; const rows = [], start = new Date().getFullYear();
                for (let y = 1; y <= 21; y++) { const d = y <= 15 ? v.d : 0; bal = (bal + d) * (1 + v.r / 100); rows.push({year: y, invested: v.d * Math.min(y, 15), value: bal}); }
                const r = growthResult(rows, 'Total investment');
                r.rows.push(['Maturity year', String(start + 21)], ["Girl's age at maturity", yr(v.age + 21)]);
                return r;
            },
        },
        'post-office-mis': {
            inputs: [select('t', 'Account', 'Single', ['Single', 'Joint']), money('p', 'Total investment', 900000, 1000, 1500000, 1000), rate('r', 'Rate of interest', 7.4, 1, 12, 0.05)],
            compute: v => { const p = Math.min(v.p, v.t === 'Single' ? 900000 : 1500000), monthly = p * v.r / 1200;
                return {donut: {labels: ['Investment', 'Interest over 5 years'], values: [p, monthly * 60]},
                    rows: [['Monthly income', inr(monthly)], ['Total interest (5 years)', inr(monthly * 60)], ['Amount invested', inr(p) + (p < v.p ? ' (limit)' : '')], ['Maturity amount', inr(p)]]}; },
        },
        'simple-interest': {
            inputs: [money('p', 'Principal amount', 100000, 1000, 100000000, 1000), rate('r', 'Rate of interest (p.a.)', 8, 1, 30), years('y', 'Time period', 5, 1, 40)],
            compute: v => growthResult(Array.from({length: v.y}, (_, i) => ({year: i + 1, invested: v.p, value: v.p * (1 + v.r / 100 * (i + 1))})), 'Principal amount'),
        },
        'compound-interest': {
            inputs: [money('p', 'Principal amount', 100000, 1000, 100000000, 1000), rate('r', 'Rate of interest (p.a.)', 8, 1, 30), years('y', 'Time period', 5, 1, 40),
                select('n', 'Compounding', 'Yearly', ['Monthly', 'Quarterly', 'Half-yearly', 'Yearly'])],
            compute: v => { const n = {Monthly: 12, Quarterly: 4, 'Half-yearly': 2, Yearly: 1}[v.n];
                return growthResult(Array.from({length: v.y}, (_, i) => ({year: i + 1, invested: v.p, value: v.p * (1 + v.r / 100 / n) ** (n * (i + 1))})), 'Principal amount'); },
        },

        epf: {
            inputs: [money('basic', 'Monthly basic + DA', 30000, 1000, 1000000, 500), {k: 'age', label: 'Your age', value: 30, min: 18, max: 57, step: 1, suffix: 'Yr'},
                {k: 'ret', label: 'Retirement age', value: 58, min: 40, max: 60, step: 1, suffix: 'Yr'}, money('bal', 'Current EPF balance', 0, 0, 10000000, 1000),
                rate('emp', 'Your contribution', 12, 12, 20, 1), rate('hike', 'Yearly salary increase', 5, 0, 20, 0.5), rate('r', 'EPF interest rate', 8.25, 1, 12, 0.05)],
            compute: v => {
                // Employer's 12%: 8.33% of wages up to ₹15,000 goes to the pension scheme (EPS), the rest to EPF.
                let bal = v.bal, mine = 0, employer = 0, interest = 0; const rows = [];
                for (let y = 1; y <= v.ret - v.age; y++) {
                    const basic = v.basic * (1 + v.hike / 100) ** (y - 1); let yearInterest = 0;
                    for (let m = 0; m < 12; m++) {
                        const e = basic * v.emp / 100, er = basic * 0.12 - Math.min(basic, 15000) * 0.0833;
                        bal += e + er; mine += e; employer += er; yearInterest += bal * v.r / 1200;
                    }
                    bal += yearInterest; interest += yearInterest; rows.push({year: y, invested: v.bal + mine + employer, value: bal});
                }
                const r = growthResult(rows, 'Total contribution');
                r.donut = {labels: ['Your contribution', "Employer's contribution", 'Interest'], values: [mine + v.bal, employer, interest]};
                r.rows = [['Your contribution', inr(mine)], ["Employer's contribution (EPF)", inr(employer)], ['Interest earned', inr(interest)], ['EPF at retirement', inr(bal)]];
                return r;
            },
        },
        nps: {
            inputs: [money('m', 'Monthly investment', 5000, 500, 500000, 500), {k: 'age', label: 'Your age', value: 30, min: 18, max: 59, step: 1, suffix: 'Yr'},
                rate('r', 'Expected return', 10, 1, 15), rate('ann', 'Annuity purchase (min 40%)', 40, 40, 100, 5), rate('ar', 'Expected annuity rate', 6, 1, 12, 0.1)],
            compute: v => {
                const rows = grow(60 - v.age, v.r / 1200, () => v.m), last = rows[rows.length - 1];
                const r = growthResult(rows, 'Total investment');
                const annuity = last.value * v.ann / 100;
                r.rows.push(['Lump sum at 60', inr(last.value - annuity)], ['Annuity corpus', inr(annuity)], ['Monthly pension', inr(annuity * v.ar / 1200)]);
                return r;
            },
        },
        apy: {
            inputs: [{k: 'age', label: 'Joining age', value: 25, min: 18, max: 40, step: 1, suffix: 'Yr'}, select('pension', 'Monthly pension wanted', '5000', ['1000', '2000', '3000', '4000', '5000'])],
            compute: v => {
                // PFRDA contribution chart (monthly contribution by joining age 18–40).
                const chart = {1000: [42, 46, 50, 54, 59, 64, 70, 76, 82, 90, 97, 106, 116, 126, 138, 151, 165, 181, 198, 218, 240, 264, 291],
                    2000: [84, 92, 100, 108, 117, 127, 139, 151, 164, 178, 194, 212, 231, 252, 276, 302, 330, 362, 396, 436, 480, 528, 582],
                    3000: [126, 138, 150, 162, 177, 192, 208, 226, 246, 268, 292, 318, 347, 379, 414, 453, 495, 543, 594, 654, 720, 792, 873],
                    4000: [168, 183, 198, 215, 234, 254, 277, 301, 327, 356, 388, 423, 462, 504, 551, 602, 659, 722, 792, 870, 957, 1054, 1164],
                    5000: [210, 228, 248, 269, 292, 318, 346, 376, 409, 446, 485, 529, 577, 630, 689, 752, 824, 902, 990, 1087, 1196, 1318, 1454]};
                const corpus = {1000: 170000, 2000: 340000, 3000: 510000, 4000: 680000, 5000: 850000};
                const c = chart[v.pension][v.age - 18], months = (60 - v.age) * 12;
                return {
                    donut: {labels: ['Total contribution', 'Corpus returned to nominee'], values: [c * months, corpus[v.pension]]},
                    rows: [['Monthly contribution', inr(c)], ['Contribution period', yr(60 - v.age)], ['Total contribution', inr(c * months)], ['Pension from age 60', inr(+v.pension) + ' a month'], ['Corpus to nominee', inr(corpus[v.pension])]],
                    table: {head: ['Pension', 'Monthly contribution at age ' + v.age, 'Total contribution'], rows: Object.keys(chart).map(p => [inr(+p), inr(chart[p][v.age - 18]), inr(chart[p][v.age - 18] * months)])},
                };
            },
        },
        pension: {
            inputs: [{k: 'age', label: 'Current age', value: 30, min: 18, max: 59, step: 1, suffix: 'Yr'}, {k: 'ret', label: 'Retirement age', value: 60, min: 40, max: 70, step: 1, suffix: 'Yr'},
                {k: 'life', label: 'Life expectancy', value: 85, min: 60, max: 100, step: 1, suffix: 'Yr'}, money('exp', 'Monthly expenses today', 50000, 5000, 1000000, 1000),
                rate('inf', 'Inflation', 6, 0, 15), rate('pre', 'Return before retirement', 12, 1, 20), rate('post', 'Return after retirement', 7, 1, 15)],
            compute: v => {
                const yearsTo = Math.max(v.ret - v.age, 1), months = yearsTo * 12, after = Math.max(v.life - v.ret, 1) * 12;
                const expAtRet = v.exp * (1 + v.inf / 100) ** yearsTo;
                const real = ((1 + v.post / 100) / (1 + v.inf / 100)) ** (1 / 12) - 1;
                const corpus = real === 0 ? expAtRet * after : expAtRet * (1 - (1 + real) ** -after) / real * (1 + real);
                const i = v.pre / 1200, sip = corpus * i / (((1 + i) ** months - 1) * (1 + i));
                const r = growthResult(grow(yearsTo, i, () => sip), 'Total investment');
                r.rows = [['Retirement corpus needed', inr(corpus)], ['Monthly expenses at retirement', inr(expAtRet)], ['Monthly SIP needed', inr(sip)], ['Total investment', inr(sip * months)]];
                return r;
            },
        },
        gratuity: {
            inputs: [money('s', 'Monthly basic + DA', 50000, 1000, 1000000, 500), {k: 'y', label: 'Years of service', value: 10, min: 5, max: 50, step: 1, suffix: 'Yr'}, {k: 'mo', label: 'Extra months', value: 0, min: 0, max: 11, step: 1, suffix: 'mo'}],
            compute: v => {
                // Payment of Gratuity Act: 15 days' wages per completed year (6+ months rounds up), 26 working days a month.
                const yrs = v.y + (v.mo >= 6 ? 1 : 0), g = 15 * v.s * yrs / 26, free = Math.min(g, 2000000);
                return {donut: {labels: ['Tax-free', 'Taxable'], values: [free, g - free]},
                    rows: [['Gratuity', inr(g)], ['Years counted', yr(yrs)], ['Tax-free (up to ₹20 lakh)', inr(free)], ['Taxable', inr(g - free)]]};
            },
        },
        hra: {
            inputs: [money('basic', 'Monthly basic salary', 50000, 1000, 1000000, 500), money('da', 'Monthly DA', 0, 0, 500000, 500), money('hra', 'Monthly HRA received', 20000, 0, 500000, 500),
                money('rent', 'Monthly rent paid', 18000, 0, 500000, 500), select('metro', 'City', 'Metro (Delhi, Mumbai, Kolkata, Chennai)', ['Metro (Delhi, Mumbai, Kolkata, Chennai)', 'Non-metro'])],
            compute: v => {
                const sal = (v.basic + v.da) * 12, hra = v.hra * 12, rent = v.rent * 12;
                const limits = [hra, Math.max(rent - 0.1 * sal, 0), sal * (v.metro.startsWith('Metro') ? 0.5 : 0.4)], exempt = Math.min(...limits);
                return {donut: {labels: ['Exempt HRA', 'Taxable HRA'], values: [exempt, hra - exempt]},
                    rows: [['Exempt HRA (yearly)', inr(exempt)], ['Taxable HRA (yearly)', inr(hra - exempt)], ['HRA received', inr(limits[0])], ['Rent paid − 10% of salary', inr(limits[1])], [(v.metro.startsWith('Metro') ? '50%' : '40%') + ' of salary', inr(limits[2])]]};
            },
        },
        gst: {
            inputs: [money('a', 'Amount', 10000, 1, 100000000, 1), select('r', 'GST rate', '18', ['3', '5', '12', '18', '28']), select('t', 'Amount is', 'Excluding GST', ['Excluding GST', 'Including GST'])],
            compute: v => {
                const r = +v.r, net = v.t === 'Excluding GST' ? v.a : v.a * 100 / (100 + r), tax = net * r / 100;
                return {donut: {labels: ['Net amount', 'CGST', 'SGST'], values: [net, tax / 2, tax / 2]},
                    rows: [['Net amount', inr(net, 2)], ['GST', inr(tax, 2)], ['CGST (' + r / 2 + '%)', inr(tax / 2, 2)], ['SGST (' + r / 2 + '%)', inr(tax / 2, 2)], ['IGST (inter-state, ' + r + '%)', inr(tax, 2)], ['Total amount', inr(net + tax, 2)]]};
            },
        },
        depreciation: {
            inputs: [money('c', 'Cost of asset', 1000000, 1000, 1000000000, 1000), money('s', 'Salvage value', 100000, 0, 500000000, 1000), years('y', 'Useful life', 5, 1, 40),
                select('m', 'Method', 'Straight line (SLM)', ['Straight line (SLM)', 'Written-down value (WDV)']), rate('w', 'WDV rate', 25, 1, 80, 0.5)],
            compute: v => {
                let open = v.c; const rows = [];
                for (let y = 1; y <= v.y; y++) {
                    const dep = v.m.startsWith('Straight') ? (v.c - v.s) / v.y : Math.min(open * v.w / 100, Math.max(open - v.s, 0));
                    rows.push([y, open, dep, open - dep]); open -= dep;
                }
                const total = rows.reduce((a, r) => a + r[2], 0);
                return {donut: {labels: ['Total depreciation', 'Book value left'], values: [total, open]},
                    rows: [['Total depreciation', inr(total)], ['Book value after ' + yr(v.y), inr(open)], ['First-year depreciation', inr(rows[0][2])]],
                    chart: {type: 'bar', title: 'Depreciation and book value', labels: rows.map(r => 'Year ' + r[0]), datasets: [{label: 'Depreciation', data: rows.map(r => Math.round(r[2])), color: C.orange}, {label: 'Closing book value', data: rows.map(r => Math.round(r[3])), color: C.blue}]},
                    table: {head: ['Year', 'Opening value', 'Depreciation', 'Closing value'], rows: rows.map(r => [r[0], inr(r[1]), inr(r[2]), inr(r[3])])}};
            },
        },
    };
})();
</script>

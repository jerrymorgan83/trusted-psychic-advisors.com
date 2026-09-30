(function () {
    'use strict';

    var root = document.getElementById('advisor-compare');
    if (!root) return;

    var search = document.getElementById('advisor-compare-search');
    var results = document.getElementById('advisor-compare-results');
    var status = document.getElementById('advisor-compare-status');
    var selectedBox = document.getElementById('advisor-compare-selected');
    var tableBox = document.getElementById('advisor-compare-table');
    var budgetInput = document.getElementById('advisor-compare-budget');
    var index = null;
    var loading = null;
    var selected = [];

    function fromVisibleCards() {
        var cards = Array.from(document.querySelectorAll('.psychic-grid .psychic-card'));
        if (!cards.length) return null;
        var specialties = [];
        var specialtyIds = {};
        var profiles = cards.map(function (card) {
            var link = card.querySelector('.psychic-card__link');
            var name = card.querySelector('.psychic-card__name');
            if (!link || !name) return null;
            var path = new URL(link.href, location.href).pathname;
            var slug = path.split('/').filter(Boolean).pop();
            var score = card.querySelector('.psychic-card__score');
            var reviews = card.querySelector('.psychic-card__reviews');
            var rate = card.querySelector('.price-tag');
            var specialty = Array.from(card.querySelectorAll('.psychic-card__tags .tag')).map(function (tag) {
                var label = tag.textContent.trim();
                if (!(label in specialtyIds)) {
                    specialtyIds[label] = specialties.length;
                    specialties.push(['', label]);
                }
                return specialtyIds[label];
            });
            var value = rate && /\$\s*([\d.]+)\s*\/\s*min/i.exec(rate.textContent);
            return [slug, name.textContent.trim(), score ? parseFloat(score.textContent) || 0 : 0,
                reviews ? parseInt(reviews.textContent.replace(/\D/g, ''), 10) || 0 : 0,
                0, value ? Number(value[1]) : 0, 0, 0, 0, specialty, [], [], [], ''];
        }).filter(Boolean);
        if (!profiles.length) return null;
        var note = document.createElement('p');
        note.className = 'advisor-compare__limit';
        note.textContent = 'Comparison currently includes the profiles shown on this catalog page.';
        root.querySelector('.advisor-compare__intro').appendChild(note);
        search.placeholder = 'Search profiles on this page';
        return { p: profiles, sp: specialties, pb: '/psychics/' };
    }

    function fetchIndex() {
        if (index) return Promise.resolve(index);
        if (loading) return loading;
        status.textContent = 'Loading published profiles…';
        loading = fetch(root.getAttribute('data-index-url'), { credentials: 'same-origin' })
            .then(function (response) {
                if (!response.ok) throw new Error('Index unavailable');
                return response.json();
            })
            .then(function (data) {
                if (!data || !Array.isArray(data.p)) throw new Error('Invalid index');
                index = data;
                status.textContent = 'Select two profiles to compare.';
                return data;
            })
            .catch(function () {
                index = fromVisibleCards();
                if (index) {
                    status.textContent = 'Select two profiles to compare.';
                    return index;
                }
                status.textContent = 'Profiles could not be loaded. Try again later.';
                loading = null;
                return null;
            });
        return loading;
    }

    function closeResults() {
        results.hidden = true;
        results.replaceChildren();
        search.setAttribute('aria-expanded', 'false');
    }

    function profileUrl(row) {
        return (index.pb || '/psychics/') + encodeURIComponent(row[0]) + '/';
    }

    function loadSource(row) {
        if (row[13] || row[14]) return;
        row[14] = true;
        fetch(profileUrl(row), { credentials: 'same-origin' }).then(function (response) {
            if (!response.ok) return null;
            return response.text();
        }).then(function (html) {
            if (!html) return;
            var page = new DOMParser().parseFromString(html, 'text/html');
            var link = page.querySelector('.source-list a[href^="http"]');
            if (link && /^https?:$/.test(new URL(link.href).protocol)) {
                row[13] = link.href;
                render();
            }
        }).catch(function () {});
    }

    function sourceUrl(row) {
        try {
            var url = new URL(row[13]);
            return /^https?:$/.test(url.protocol) ? url.href : '';
        } catch (error) {
            return '';
        }
    }

    function price(row) {
        var value = Number(row[5]);
        return Number.isFinite(value) && value > 0 ? value : null;
    }

    function money(value) {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
    }

    function listMatches() {
        closeResults();
        if (!index) return;
        if (selected.length >= 3) {
            status.textContent = 'Remove a profile to choose another.';
            return;
        }
        var query = search.value.trim().toLocaleLowerCase();
        if (query.length < 2) return;
        var matches = index.p.filter(function (row) {
            return !selected.some(function (item) { return item[0] === row[0]; }) &&
                String(row[1]).toLocaleLowerCase().includes(query);
        }).slice(0, 8);
        if (!matches.length) {
            status.textContent = 'No matching profile found.';
            return;
        }
        matches.forEach(function (row) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'advisor-compare__result';
            button.setAttribute('role', 'option');
            button.textContent = row[1] + (price(row) ? ' · ' + money(price(row)) + '/min' : ' · Rate not listed');
            button.addEventListener('click', function () {
                if (selected.length >= 3) return;
                selected.push(row);
                loadSource(row);
                search.value = '';
                closeResults();
                render();
                search.focus();
            });
            results.appendChild(button);
        });
        results.hidden = false;
        search.setAttribute('aria-expanded', 'true');
        status.textContent = selected.length === 3 ? 'Remove a profile to choose another.' : 'Choose a matching profile.';
    }

    function specialty(row) {
        return (Array.isArray(row[9]) ? row[9] : []).slice(0, 3).map(function (id) {
            return index.sp[id] ? index.sp[id][1] : '';
        }).filter(Boolean).join(', ') || 'Not listed';
    }

    function addCell(tr, tag, content) {
        var cell = document.createElement(tag);
        cell.textContent = content;
        tr.appendChild(cell);
        return cell;
    }

    function render() {
        selectedBox.replaceChildren();
        tableBox.replaceChildren();
        selected.forEach(function (row, position) {
            var chip = document.createElement('span');
            chip.className = 'advisor-compare__chip';
            chip.appendChild(document.createTextNode(row[1] + ' '));
            var remove = document.createElement('button');
            remove.type = 'button';
            remove.textContent = '×';
            remove.setAttribute('aria-label', 'Remove ' + row[1]);
            remove.addEventListener('click', function () {
                selected.splice(position, 1);
                render();
            });
            chip.appendChild(remove);
            selectedBox.appendChild(chip);
        });

        if (selected.length < 2) {
            tableBox.hidden = true;
            status.textContent = selected.length ? 'Choose one more profile to compare.' : 'Select two profiles to compare.';
            return;
        }

        var budget = Number(budgetInput.value);
        var validBudget = Number.isFinite(budget) && budget >= 1 && budget <= 10000;
        var table = document.createElement('table');
        table.className = 'advisor-compare__table';
        var caption = document.createElement('caption');
        caption.textContent = 'Published profile comparison';
        table.appendChild(caption);
        var head = document.createElement('thead');
        var headRow = document.createElement('tr');
        addCell(headRow, 'th', 'Detail').scope = 'col';
        selected.forEach(function (row) { addCell(headRow, 'th', row[1]).scope = 'col'; });
        head.appendChild(headRow);
        table.appendChild(head);
        var body = document.createElement('tbody');

        var fields = [
            ['Published rate', function (row) { return price(row) ? money(price(row)) + '/min' : 'Not listed'; }],
            ['Published rating', function (row) { return Number(row[3]) > 0 ? Number(row[2]).toFixed(1) + ' / 5' : 'Not listed'; }],
            ['Published reviews', function (row) { return Number(row[3]) > 0 ? Number(row[3]).toLocaleString('en-US') : 'None listed'; }],
            ['Specialties', specialty],
            ['Estimated minutes for budget', function (row) { return validBudget && price(row) ? (budget / price(row)).toFixed(1) + ' min' : 'Unavailable'; }]
        ];
        fields.forEach(function (field) {
            var tr = document.createElement('tr');
            addCell(tr, 'th', field[0]).scope = 'row';
            selected.forEach(function (row) { addCell(tr, 'td', field[1](row)); });
            body.appendChild(tr);
        });

        var links = document.createElement('tr');
        addCell(links, 'th', 'Profiles and sources').scope = 'row';
        selected.forEach(function (row) {
            var cell = document.createElement('td');
            var profile = document.createElement('a');
            profile.href = profileUrl(row);
            profile.textContent = 'View profile';
            cell.appendChild(profile);
            var source = sourceUrl(row);
            if (source) {
                cell.appendChild(document.createTextNode(' · '));
                var sourceLink = document.createElement('a');
                sourceLink.href = source;
                sourceLink.target = '_blank';
                sourceLink.rel = 'nofollow noopener noreferrer';
                sourceLink.textContent = 'Source page';
                cell.appendChild(sourceLink);
            }
            links.appendChild(cell);
        });
        body.appendChild(links);
        table.appendChild(body);
        tableBox.appendChild(table);
        tableBox.hidden = false;
        status.textContent = selected.length === 3 ? 'Three profiles selected.' : 'Choose one more profile, or review this comparison.';
    }

    search.addEventListener('focus', function () { fetchIndex().then(listMatches); });
    search.addEventListener('input', function () { fetchIndex().then(listMatches); });
    search.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeResults();
    });
    budgetInput.addEventListener('input', render);
    document.addEventListener('click', function (event) {
        if (!root.contains(event.target)) closeResults();
    });
}());

{{--
    Karty (zakładki) w długich ekranach panelu, np. Ustawienia → Serwis. Zamiast jednej długiej strony widać jedną kartę naraz.

    Znaczniki w widoku:
      - kontener grupy: data-cards="klucz" (np. nazwa zakładki ustawień) i opcjonalnie data-cards-label="Opis listy kart";
      - każda karta: data-card="slug" data-card-label="Napis na karcie";
      - nagłówek karty powtarzający napis na karcie: data-card-title (przy kartach ukryty wizualnie, zostaje dla czytnika ekranu).
    Elementy w grupie bez data-card (np. podsumowanie na górze) są zawsze widoczne. Pasek kart staje przed pierwszą kartą.

    Wygląd „pivot” jak w Metro UI: duże napisy w rzędzie, otwarta karta pogrubiona i podkreślona kolorem marki (nie tylko kolor),
    treść karty płasko — bez ramki, tła i wewnętrznego odstępu (te zostają tylko bez JS, gdy karty stoją jedna pod drugą).

    Skrypt buduje pasek z role="tablist"/"tab"/"tabpanel" (strzałki ←/→, Home/End), zapamiętuje wybór w adresie (?karta=slug),
    oznacza karty z błędem walidacji (ikona + tekst dla czytnika) i z niezapisanymi zmianami. Grupa z mniej niż dwiema kartami
    zostaje bez zmian. Bez JS wszystkie karty są widoczne jedna pod drugą, jak dawniej. Ukryte karty zostają w formularzu,
    więc zapis obejmuje wszystkie karty naraz.

    API: window.adminSectionCards.open(element) — otwiera kartę z elementem (np. polem z błędem);
         window.adminSectionCards.current(container) — slug otwartej karty w grupie wewnątrz kontenera albo ''.
--}}
<style>
    [data-card][data-card-hidden] { display: none !important; }
    .sc-tablist { display: flex; flex-wrap: wrap; column-gap: 1.75rem; row-gap: .25rem; margin: 0 0 1.75rem; border-bottom: 1px solid #d1d5db; }
    .sc-tab { display: inline-flex; align-items: center; gap: .5rem; min-height: 2.75rem; margin-bottom: -1px; padding: .375rem 0 .625rem;
        border: 0; border-radius: 0; background: none; color: #4b5563; font-size: 1.125rem; font-weight: 400; line-height: 1.3;
        text-align: left; cursor: pointer; box-shadow: inset 0 -3px 0 transparent; }
    .sc-tab:hover { color: var(--color-ink, #1a1a1a); box-shadow: inset 0 -3px 0 #9ca3af; }
    .sc-tab[aria-selected="true"] { color: var(--color-ink, #1a1a1a); font-weight: 600; box-shadow: inset 0 -3px 0 var(--m-accent, var(--color-brand-dark, #1752bf)); }
    .sc-tab:focus-visible { outline: 2px solid var(--color-brand-dark, #1752bf); outline-offset: 2px; }
    .sc-tab .sc-flags { display: inline-flex; align-items: center; gap: .25rem; font-size: .875rem; }
    .sc-tab .sc-flags:empty { display: none; }
    .sc-tab .sc-err { color: #b91c1c; }
    .sc-tab .sc-dirty { display: inline-block; width: .5rem; height: .5rem; border-radius: 9999px; background: currentColor; }
    /* Treść karty płasko pod paskiem: bez ramki, tła, odstępu i górnej kreski. */
    .sc-on [data-card] { margin-top: 0 !important; padding: 0 !important; border: 0 !important; border-radius: 0 !important; background: none !important; box-shadow: none !important; }
    .sc-on [data-card] > .border-t:first-child, .sc-on [data-card-title] + .border-t { border-top-width: 0 !important; padding-top: 0 !important; }
    .sc-on [data-card-title] { position: absolute !important; width: 1px; height: 1px; margin: -1px; padding: 0; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
    .sc-on [data-card-title] + * { margin-top: 0 !important; }
    .sc-on [data-card]:focus-visible { outline: 2px solid var(--color-brand-dark, #1752bf); outline-offset: 4px; }
</style>
<script>
    (function () {
        if (window.adminSectionCards) return;

        var groups = [];

        function cardsOf(group) {
            return Array.prototype.filter.call(group.querySelectorAll('[data-card]'), function (card) {
                return card.closest('[data-cards]') === group;
            });
        }

        function setParam(key) {
            try {
                var url = new URL(location.href);
                url.searchParams.set('karta', key);
                history.replaceState(history.state, '', url.toString());
            } catch (e) {}
        }

        function setup(group, index) {
            var cards = cardsOf(group);
            if (cards.length < 2) return;

            var prefix = 'sc-' + String(group.dataset.cards || index).replace(/[^a-z0-9_-]/gi, '');
            var list = document.createElement('div');
            list.className = 'sc-tablist';
            list.setAttribute('role', 'tablist');
            list.setAttribute('aria-label', group.dataset.cardsLabel || 'Karty tej sekcji');

            var state = { group: group, cards: cards, buttons: [], current: null, select: null };

            cards.forEach(function (card) {
                var key = card.dataset.card;
                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'sc-tab';
                button.id = prefix + '-tab-' + key;
                button.setAttribute('role', 'tab');
                if (! card.id) card.id = prefix + '-karta-' + key;
                button.setAttribute('aria-controls', card.id);

                var label = document.createElement('span');
                label.textContent = card.dataset.cardLabel || key;
                button.appendChild(label);
                var flags = document.createElement('span');
                flags.className = 'sc-flags';
                button.appendChild(flags);

                card.setAttribute('role', 'tabpanel');
                card.setAttribute('aria-labelledby', button.id);
                card.tabIndex = 0;

                button.addEventListener('click', function () { select(key, false, true); });
                list.appendChild(button);
                state.buttons.push(button);
            });

            function select(key, focus, remember) {
                var found = cards.some(function (card) { return card.dataset.card === key; });
                if (! found) key = cards[0].dataset.card;
                state.current = key;
                cards.forEach(function (card, i) {
                    var on = card.dataset.card === key;
                    if (on) card.removeAttribute('data-card-hidden'); else card.setAttribute('data-card-hidden', '');
                    state.buttons[i].setAttribute('aria-selected', on ? 'true' : 'false');
                    state.buttons[i].tabIndex = on ? 0 : -1;
                    if (on && focus) state.buttons[i].focus();
                });
                if (remember) setParam(key);
            }
            state.select = select;

            list.addEventListener('keydown', function (event) {
                var keys = cards.map(function (card) { return card.dataset.card; });
                var i = keys.indexOf(state.current);
                var next = { ArrowRight: i + 1, ArrowLeft: i - 1, Home: 0, End: keys.length - 1 }[event.key];
                if (next === undefined) return;
                event.preventDefault();
                select(keys[(next + keys.length) % keys.length], true, true);
            });

            // Kropka przy karcie, w której coś zmieniono, a jeszcze nie zapisano.
            group.addEventListener('input', markDirty);
            group.addEventListener('change', markDirty);
            function markDirty(event) {
                if (! event.target.name) return; // pola pomocnicze bez nazwy (np. autokonfigurator) nic nie zapisują
                var card = event.target.closest && event.target.closest('[data-card]');
                var i = cards.indexOf(card);
                if (i < 0) return;
                var flagsBox = state.buttons[i].querySelector('.sc-flags');
                if (flagsBox.querySelector('.sc-dirty')) return;
                var dot = document.createElement('span');
                dot.className = 'sc-dirty';
                dot.setAttribute('aria-hidden', 'true');
                var text = document.createElement('span');
                text.className = 'sr-only';
                text.textContent = ' (niezapisane zmiany)';
                flagsBox.appendChild(dot);
                flagsBox.appendChild(text);
            }

            cards[0].parentNode.insertBefore(list, cards[0]);
            group.classList.add('sc-on');

            var wanted = null;
            try { wanted = new URL(location.href).searchParams.get('karta'); } catch (e) {}
            select(wanted || cards[0].dataset.card, false, false);
            groups.push(state);
        }

        function stateOf(element) {
            var card = element && element.closest ? element.closest('[data-card]') : null;
            if (! card) return null;
            for (var i = 0; i < groups.length; i++) {
                if (groups[i].cards.indexOf(card) >= 0) return { state: groups[i], card: card };
            }
            return null;
        }

        function markError(element) {
            var found = stateOf(element);
            if (! found) return;
            var button = found.state.buttons[found.state.cards.indexOf(found.card)];
            var flagsBox = button.querySelector('.sc-flags');
            if (flagsBox.querySelector('.sc-err')) return;
            var icon = document.createElement('i');
            icon.className = 'fa-solid fa-circle-exclamation sc-err';
            icon.setAttribute('aria-hidden', 'true');
            var text = document.createElement('span');
            text.className = 'sr-only';
            text.textContent = ' (zawiera błąd)';
            flagsBox.insertBefore(text, flagsBox.firstChild);
            flagsBox.insertBefore(icon, flagsBox.firstChild);
        }

        window.adminSectionCards = {
            open: function (element) {
                var found = stateOf(element);
                if (found) found.state.select(found.card.dataset.card, false, true);
                return !! found;
            },
            markError: markError,
            current: function (container) {
                for (var i = 0; i < groups.length; i++) {
                    if (container && container.contains(groups[i].group)) return groups[i].current || '';
                }
                return '';
            },
        };

        document.querySelectorAll('[data-cards]').forEach(setup);
    })();
</script>

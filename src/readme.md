## Nutzungsanleitung

### Einstellungen

#### Mannschaft abspeichern

1. Oben den Reiter *Mannschaften bearbeiten* öffnen.
2. **Falls** eine neue Mannschaft hinzugefügt werden soll auf *Mannschaft hinzufügen* klicken. **Sonst** bei der Mannschaft, die bearbeitet werden soll auf *Bearbeiten* klicken.
3. *Kürzel*: Ein kürzel Auswählen, das die Mannschaft innerhalb der Seite eindeutig definiert.
    Es wird z.B. bei den Widgets benötigt um die Mannschaft auszuwählen. Es empfehlen sich eindeutige Abkürzungen wie *mA1* für männliche A1-Jugend oder *d1* für Damen 1.
4. *Name*: Der Name, der verwendet wird um den Vereinsnamen zu ersetzen.
    In Spielplänen und Tabellen kann anstatt dem Vereinsnamen der Mannschaftsname angezeigt werden. Der Name sollte Vereinsintern wiedererkannt werden.
5. *Link (Liga)*: Der Link, welcher auf [Handball4All] zur Liga führt.
    Link finden:
    1. [Handball4All] öffnen.
    2. Entsprechenden Bereich auswählen.
    3. Entsprechende Liga auswählen.
    4. **Wichtig**: Die Mannschaft aus der Tabelle auswählen.
    5. Den Link aus der Addresszeile kopieren und ins entsprechende Feld einfügen.
6. *Link (Pokal)*: Der Link, welcher zum Pokalwettbewerb der Mannschaft führt (falls vorhanden).
    Link finden: Wie oben, jedoch Schritt 4 auslassen.
7. Auf den Button *Speichern* klicken.

### Widgets

Die folgenden Widgets werden vom Plugin bereitgestellt.
Zu allen Widgets ist ein äquivalenter Shortcode vorhanden, der an Stellen verwendet werden kann, an denen keine Widgets eingesetzt werden können.
Die Angaben in Klammern geben Hinweise auf die Verwendung der Shortcodes.
Mehr Informationen zu Shortcodes gibt es in der [offiziellen Anleitung][1].

#### Game Widget

Das Game Widget ist an sich nicht verwendbar, bildet jedoch die Grundlage für das Team- und Gym Widget.
Die Einstellungen, die sich dem entsprechend auf beide dieser Widgets beziehen sind hier aufgeführt.

##### Einstellungen

1. Titel (`title`)
    Der Titel, der über den Spielen angezeigt werden soll.
	(Standard: *leer*)
2. Namen ersetzen (`replace_names`)
    Legt fest, ob der Name des eigenen Teams in jedem einzelnen Spiel ersetzt werden soll. Wenn diese Option ausgewählt ist wird das eigene Team mit dem internen Namen ersetzt (z.B. Herren 1).
	(`0`/`1` / `'false'`/`'true'`, Standard: Nein)
3. Richtung (`direction`)
    Legt fest, wie die Anzeige formatiert ist:
    * Horizontal (`'hor'`, Standard):
        Die Gegner werden nebeneinander angezeigt, das Ergebnis darunter. Benötigt viel Platz.
    * Vertikal (`'ver'`):
        Die Gegner werden übereinander angezeigt, das Ergebnis daneben. Geeignet für schmalere Ansichten, da weniger nebeneinander steht.
    * Tabelle (`'tab'`):
        Alle Spiele werden in einer Tabelle zusammengefasst; jedes Spiel benötigt nur eine Zeile. Sehr Kompakte ansicht.
4. Wettbewerbe (`'select'`)
    Wählt aus, welcher Wettbewerb (Liga, Pokal) angezeigt werden soll.
	(`'league'`, `'cup'`, `'both'`, Standard: Liga)
5. Zeitliche Einschänkung (`time_select`)
    Wählt aus welcher Zeitraum berücksichtigt werden soll.
	* Keine Einschränkung (*leer*, Standard)
	* Manuell (`'man'`)
	* Heute (`'today'`)
	* Letztes Wochenende (`'last_we'`)
	* Nächstes Wochenende (`'next_we'`)
	* Letztes und nächstes Wochenende (`'last_next_we'`)
6. vergangene Tage (`time_before`) / nächste Tage (`time_after`) (nur, wenn *Zeitliche Einschänkung* auf *Manuell*)
    Legt fest, wie viele vergangene/zukünftige Tage für die Spielauswahl verwendet werden sollen.

#### Team Games Widget

```
[gtp_team_games /]
```

Das Team Game Widget zeigt die Spiele von einem oder mehreren registrierten Teams an.

##### Einstellungen

1. Teams (`teams`)
    Eine druch Kommata getrennte Liste von Teamkürzeln von jenen Teams, deren Spiele angezeigt werden sollen.
	Wird dieses Feld freigelassen werden alle Teams ausgewählt.
	(Standard: Alle Teams)
2. Linktext (`link`):
	Wird ein Linktext angegeben wird unter der Spielanzeige ein Link zu [Handball4All] platziert.  
	**Wenn** exakt ein Team und ein bestimmter Wettbewerb ausgewählt ist zeigt der Link auf die Wettbewerbsseite dieses Teams.  
	**Sonst** wird in jedem Fall der in den allgemeinen Einstellungen gesetzte Clublink verwendet, da nur hier mehrere Teams und/oder Wettbewerbe zu sehen sind.  
	(Standart: *kein Link*)
3. *alle Einstellungen des [Game Widget]s*

#### Gym Widget

```
[gtp_gym /]
```

Das Gym Game Widget zeigt Spiele an, die in einer bestimmten Halle stattfinden.
**Hinweis**: Es können nur Spiele von Teams erfasst werden, die auch registriert sind. Spiele von anderen Vereinen oder Events aus anderen Sportarten / Organisationen werden nicht erfasst.

##### Einstellungen

1. Hallennummer (`gym_no`)
    Die Nummer der Halle, wie sie von [Handball4All](1) angegeben wird.
2. *alle Einstellungen des [Game Widget]s*

#### Table Widget

```
[gtp_table /]
```

Das Table Widget zeigt die Tabelle eines Teams an.

##### Einstellungen

1. Titel (`title`)
    Der Titel, der über der Tabelle angezeigt werden soll.
	(Standard: *leer*)
2. Team (`team`)
    Das Team, dessen Tabelle angezeigt werden soll. (vgl. [Game Widget])
3. Ansicht (`view`)
    Gibt an, ob eine breite, detailreichere, oder eine schmale, detailärmere Variante der Tabelle dargestellt wird.
    * Standard (`'standard'`, Standard)  
        | Plazierung | Teamname | Anz. Spiele | **S:U:N | Tore** | Punkte |
    * Schmal (`'slim'`)  
        | Plazierung | Teamname | Anz. Spiele | Punkte |
4. Linktext (`link`)
	Wenn der Linktext gesetzt ist wird unter der Tabelle ein Link zur Ligaseite des Teams auf [Handball4All] platziert.  
	(Standart: *kein Link*)

#### Team Widget

```
[gtp_team /]
```

Das Team Widget lässt den Nutzer ein Team auswählen und zeigt dessen Tabelle, sowie dessen gesamten Spielplan an.

### Weitere Shortcodes

#### Clublink

Mit dem Clublink-Shortcode lässt sich ein Link zur Clubseite auf Handball4All einfügen. Der Link zeigt auf die Seite, die in der Einstellung *Clublink* hinterlegt ist und öffnet in einem neuen Tab, solange das Attribut `'target'` nicht überschrieben wird.

##### Attribute
* `target` (optional): Das HTML-`target`-Attribut für HTML-`a`-Elemente. [Details][2]

##### Beispiel

```
[gtp_clublink]zur Clubansicht auf Handball4All[/gtp_clublink]
[gtp_clublink target='_self']zur Clubansicht auf Handball4All[/gtp_clublink]
```

#### Teamlink

Mit dem Teamlink-Shortcode lässt sich ein Link zur Liga- oder Pokalseite eines Teams erstellen. Der Link zeigt auf jene Seite, die beim gewählten Team eingetragen ist. das `'target'`-Attribut funktioniert identisch zum [Clublink](#clublink).

##### Attribute
* `team`: Das Team, dessen Link verwendet werden soll.
* `comp` (optional): Der Wettbewerb, dessen Link verwendet werden soll. (`'cup'` / `'league'` (Standard))
* `target` (optional)

##### Beispiel

```
[gtp_team_link team='h2']Tabelle der Herren 2 auf Handball4All[/gtp_team_link]
[gtp_team_link team='h2' comp='cup' target='_self']Pokalseite der Herren 2 auf Handball4all[/gtp_team_link]
```

[Handball4All]: https://handball4all.de
[Game Widget]: #game-widget
[1]: https://codex.wordpress.org/Shortcode
[2]: https://www.w3schools.com/tags/att_a_target.asp

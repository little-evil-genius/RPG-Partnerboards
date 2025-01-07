# RPG-Partnerboards
Das Partnerforum Plugin ermöglicht eine erweiterte Verwaltung und Darstellung von Partnerforen. Das Team kann über das ACP (Admin Control Panel) individuelle Felder erstellen, die ausgefüllt werden müssen, wenn ein neues Thema im Partnerforenbereich erstellt wird. Die erfassten Daten können im Forumdisplay sowie im Showthread angezeigt werden, um Partnerforen-Informationen klar und strukturiert zu präsentieren.<br>
<br>
Zusätzlich bietet das Plugin die Möglichkeit, Partnerforen-Informationen auf der Startseite (Index - klassisch und/oder im Forumbit) auszugeben und eine Übersicht aller Partnerforen im Moderationsbereich (ModCP).

## Funktionsübersicht
- Erstellen von benutzerdefinierten Formularfeldern im ACP mit individuellen Identifikatoren (maschinenlesbar, ohne Sonderzeichen oder Leerzeichen). Unterstützt verschiedene Feldtypen (z.B. Textfelder, Auswahlfelder) für flexible Eingabeformate, ähnlich wie bei den Profilfeldern. Möglichkeit, Felder als verpflichtend zu markieren und zu definieren, welche Benutzergruppen die Felder sehen und ausfüllen dürfen.
- Ausgabe der Partnerforen-Informationen im Forumdisplay (Template: forumdisplay_thread) und in Threads (Template: showthread).
- Ausgabe von allen, nur Foren aus einem bestimmten Forum (Sister) oder individuell entschiedene Partnerforen auf dem Index.

# Individuelle Partnerforen-Felder
Dieses Plugin bietet eine hohe Flexibilität, indem es ermöglicht, im ACP individuell angepasste Felder für Partnerforen zu erstellen. Standardmäßig sind keine Felder vorgegeben, sodass das Team je nach Anforderungen des Forums die benötigten Felder frei gestalten kann. Jedes erstellte Feld erhält einen eindeutigen Identifikator, der keine Sonderzeichen oder Leerzeichen enthalten darf, um eine maschinenlesbare Verarbeitung zu gewährleisten.<br>
<br>
Die Feldtypen können, ähnlich wie bei den Profilfeldern, frei gewählt werden. Dies ermöglicht die Nutzung unterschiedlicher Eingabeformate wie Textfelder, Auswahlfelder und mehr. Zudem kann festgelegt werden, ob ein Feld verpflichtend ausgefüllt werden muss und welche Benutzergruppen die Felder sehen und ausfüllen dürfen.

### Grafik-Element
Als Feldtyp kann unter anderem die Option gewählt werden Grafik. Dahinter versteckt sich eine Upload-Funktion wie man es vom Avatar kennt. Für die einzelnen Upload-Elemente können Dateigröße, Bildgröße und Dateiformat festgelegt werden. Die Grafiken werden dann in einen neu angelegten Ordner auf dem Webspace hochgeladen und gespeichert. Beim löschen des Feldes oder vom Thema wird diese Dateien entfernt.

# Partnerforen-Informationen im Forumdisplay und Thread
Das Plugin bietet die Möglichkeit, Partnerforen-Informationen in verschiedenen Bereichen des Forums anzuzeigen: im Forumdisplay (Template: forumdisplay_thread) und in Threads (Template: showthread).<br>
Für beide Templates gibt es eine kompakte Variable, die eine schlichte und zusammengefasste Ausgabe der Partnerforen-Informationen ermöglicht. Das Team kann die entsprechenden Templates anpassen, um die gewünschten Daten flexibel darzustellen. Die individuellen Partnerforen-Felder können jedoch auch direkt angesprochen ({$partnerboards['Identifikator']}) und entweder in das Template für die kompakte Variable ("partnerboards_forumdisplay" (Forumdisplay) und "partnerboards_showthread" (Showthread)) eingefügt oder direkt in die Templates forumdisplay_thread (Forumdisplay) und showthread (Thread) integriert werden, um die Informationen dort individuell anzuzeigen.<br>
<br>
Für maximale Flexibilität im Design stellt das Plugin zwei spezielle Variablen bereit, die direkt in die entsprechenden Templates für Forumdisplay (forumdisplay_thread) und Showthread (showthread) eingefügt werden können:<br>
- {$display_onlypartner}: Blendet Inhalte außerhalb des Partnerbereichs aus, um zu verhindern, dass Texte, Icons oder andere Gestaltungselemente im Nicht-Partner-Bereich angezeigt werden. Diese Variable arbeitet mit einem <a href="https://wiki.selfhtml.org/wiki/HTML/Attribute/style">style="display:none;"-Tag</a>, das die Anzeige des Inhalts vollständig unterdrückt und keinen Platz im Layout verbraucht.
- {$display_offpartner}: Blendet Informationen aus, die im Partnerbereich nicht sichtbar sein sollen, aber außerhalb des Partnerbereichs angezeigt werden dürfen.
<br>
Durch die Verwendung dieser Variablen kann das Layout und die Anzeige der Partnerforen-Informationen sowohl im Forumdisplay als auch in Threads nach den eigenen Wünschen gestalten, ohne das Design außerhalb des Partnerbereichs zu beeinflussen.

# Übersichtsseite
Das Plugin bietet eine zusätzliche Übersichtsseite im Moderationsbereich (ModCP), auf der alle Partnerforen-Themen und deren Informationen aufgelistet werden. Diese Übersicht ermöglicht es dem Team, schnell auf alle Partnerforen zuzugreifen und die zugehörigen Daten.<br>
Zusätzlich kann in den Einstellungen eine weitere Liste aktiviert werden, die eine Übersicht der Partnerforen für das gesamte Forum bereitstellt.

# Anzeige von Partnerforen auf dem Index
Das Plugin bietet die Möglichkeit, Partnerforen und ihre Informationen direkt auf der Startseite (Index) des Forums anzuzeigen. Diese Funktion ist flexibel anpassbar und kann über die Einstellungen nach Wunsch konfiguriert werden. So können beispielsweise Buttons, Links oder andere Gestaltungselemente ausgegeben werden, um die Partnerforen prominent darzustellen.<br>
<br>
Zur Auswahl stehen verschiedene Optionen:
- Alle Partner auflisten: Es werden alle Partnerforen angezeigt.
- Nur besondere Partner (Sister-Foren): Nur ausgewählte Foren, wie z.B. Sister-Foren aus einem bestimmten Forum, werden angezeigt.
- Individuelle Auswahl pro Partnerforum: Das Team kann für jedes Partnerforum separat im Formular festlegen, ob es auf dem Index angezeigt werden soll.<br>
<br>
Die Anzeige dieser Informationen kann komplett deaktiviert werden, falls keine Partnerforen auf der Startseite dargestellt werden sollen. So bleibt die Entscheidung zur Nutzung dieser Funktion ganz dem Forumsteam überlassen.

# Vorrausetzung
- Das ACP Modul <a href="https://github.com/little-evil-genius/rpgstuff_modul" target="_blank">RPG Stuff</a> <b>muss</b> vorhanden sein.

# Datenbank-Änderungen
hinzugefügte Tabelle:
- partnerboards
- partnerboards_fields

# Neuer Ordner - partnerboards
Es wurde ein neuer Ordner mit dem Namen partnerboards im Ordner uploads erstellt. Die hochgeladenen Dateien der Themen werden mit dem Identfikator und der tid gespeichert.

# Neue Sprachdateien
- deutsch_du/admin/partnerboards.lang.php
- deutsch_du/partnerboards.lang.php

# Einstellungen
- Bereich für die Verwaltung
- Bereich für angenommene Partner
- Kriterien für Partnerschaftsanfrage
- Darstellung auf dem Index
- Bereich für besondere Partner (Sister)
- Übersichtsseite
- Übersichtsseite - Berechtigungen

# Neue Template-Gruppe innerhalb der Design-Templates
- RPG-Partnerboards

# Neue Templates (nicht global!)
- partnerboards_forumdisplay
- partnerboards_forumdisplay_fields
- partnerboards_index
- partnerboards_index_bit
- partnerboards_modcp
- partnerboards_modcp_fields
- partnerboards_modcp_forenbit
- partnerboards_modcp_nav
- partnerboards_modcp_partnerareas
- partnerboards_newthread
- partnerboards_newthread_fields
- partnerboards_newthread_indexdisplay
- partnerboards_overview
- partnerboards_overview_fields
- partnerboards_overview_forenbit
- partnerboards_overview_partnerareas
- partnerboards_showthread
- partnerboards_showthread_fields<br>
<br>
<b>HINWEIS:</b><br>
Alle Templates wurden größtenteils ohne Tabellen-Struktur gecodet. Das Layout wurde auf ein MyBB Default Design angepasst.

# Neue Variablen
- newthread: {$newthread_partnerboards}
- editpost: {$edit_partnerboards}
- forumdisplay_thread: {$partnerboards_forumdisplay}
- showthread: {$partnerboards_showthread}
- modcp_nav_users: {$nav_partnerboards}
- index_boardstats: {$partnerboards_index}

# Neues CSS - partnerboards.css
Es wird automatisch in jedes bestehende und neue Design hinzugefügt. Man sollte es einfach einmal abspeichern - auch im Default. Nach einem MyBB Upgrade fehlt der Stylesheets im Masterstyle? Im ACP Modul "RPG Erweiterungen" befindet sich der Menüpunkt "Stylesheets überprüfen" und kann von hinterlegten Plugins den Stylesheet wieder hinzufügen.
```css
.partnerboards_modcp {
    display: flex;
    flex-direction: column;
    flex-wrap: nowrap;
    justify-content: flex-start;
    gap: 10px 0;
}

.partnerboards_modcp_head {
    display: flex;
    justify-content: space-around;
    gap: 0 10px;
    font-weight: bold;
}

.partnerboards_modcp_foren {
    -moz-border-radius-bottomright: 6px;
    -webkit-border-bottom-right-radius: 6px;
    border-bottom-right-radius: 6px;
    -moz-border-radius-bottomleft: 6px;
    -webkit-border-bottom-left-radius: 6px;
    border-bottom-left-radius: 6px;
    border: none;
    text-align: center;
}

.partnerboards_modcp_bit {
    display: flex;
    gap: 0 10px;
    padding: 5px 0;
    align-items: center;
    justify-content: space-around;
    text-align: center;
}

.partnerboards_modcp_bit div {
    width: 33%;
}

.partnerboards_modcp_head div {
    width: 33%;
    text-align: center;
}

.partnerboards_modcp_bit div:nth-child(2) {
    text-align: left;
}

.partnerboards_showthread-bit {
	display: flex;
	justify-content: space-between;
	padding: 10px 0;
	border-bottom: 1px solid #ddd;
}

.partnerboards_showthread-bit:last-child {
	border-bottom: none;
}

.partnerboards_showthread-label {
	width: 20%;
	font-weight: bold;
}

.partnerboards_showthread-value {
	flex-grow: 1;
}

.partnerboards_overview_head {
    display: flex;
    justify-content: space-around;
    gap: 0 10px;
    font-weight: bold;
}

.partnerboards_overview_foren {
    -moz-border-radius-bottomright: 6px;
    -webkit-border-bottom-right-radius: 6px;
    border-bottom-right-radius: 6px;
    -moz-border-radius-bottomleft: 6px;
    -webkit-border-bottom-left-radius: 6px;
    border-bottom-left-radius: 6px;
    border: none;
    text-align: center;
}

.partnerboards_overview_bit {
    display: flex;
    gap: 0 10px;
    padding: 5px 0;
    align-items: center;
    justify-content: space-around;
    text-align: center;
}

.partnerboards_overview_bit div {
    width: 33%;
}

.partnerboards_overview_head div {
    width: 33%;
    text-align: center;
}

.partnerboards_overview_bit div:nth-child(2) {
    text-align: left;
}
```

# Benutzergruppen-Berechtigungen setzen
Damit alle Admin-Accounts Zugriff auf die Verwaltung der Partnerbereichfeler haben im ACP, müssen unter dem Reiter Benutzer & Gruppen » Administrator-Berechtigungen » Benutzergruppen-Berechtigungen die Berechtigungen einmal angepasst werden. Die Berechtigungen für die Partnerforen befinden sich im Tab 'RPG Erweiterungen'.

# Links
<b>ACP</b><br>
admin/index.php?module=rpgstuff-partnerboards<br>
<br>
<b>ModCP</b><br>
modcp.php?action=partnerboards<br>
<br>
<b>Übersicht der aller Partnerforen</b><br>
misc.php?action=partnerboards

# Demo
### ACP
<img src="https://stormborn.at/plugins/partnerboards_acp_overview.png"><br>
<img src="https://stormborn.at/plugins/partnerboards_acp_add.png"><br>
<img src="https://stormborn.at/plugins/partnerboards_acp_add_type.png">

### Showthread
<img src="https://stormborn.at/plugins/partnerboards_showthread.png">

### Forumdisplay
<img src="https://stormborn.at/plugins/partnerboards_forumdisplay.png">

### ModCP
<img src="https://stormborn.at/plugins/partnerboards_modcp.png">

### Übersicht
<img src="https://stormborn.at/plugins/partnerboards_overview.png">

### Index
<img src="https://stormborn.at/plugins/partnerboards_index.png">

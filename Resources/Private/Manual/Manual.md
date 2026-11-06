---
version: "1.0"
date: 2026-11-06
---

# Handbuch: MJML

Newsletter- und Systemmails können als MJML geschrieben werden. Die Extension wandelt die Vorlage über das MJML-Programm in HTML um.

## Vorlagen

MJML-Dateien liegen bei der Extension, die die Mail verschickt. Fluid kann die Vorlage zuerst füllen, danach folgt die Umwandlung nach HTML.

Der Versand selbst läuft weiter über **Mail**.

## Programm-Pfad

Der Pfad zur MJML-Binary steht in der Extension-Konfiguration. Ohne erreichbares Programm schlägt das Rendern fehl, die Warteschlange bleibt dann auf dem Fehler stehen.

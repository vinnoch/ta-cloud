from pathlib import Path
import csv
import re

from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER, TA_LEFT
from reportlab.lib.pagesizes import A4, landscape
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import mm
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.graphics.shapes import Drawing, Path as ShapePath
from reportlab.platypus import (
    BaseDocTemplate, Frame, PageTemplate, Paragraph, Spacer, Table, TableStyle,
    PageBreak, KeepTogether
)

ROOT = Path(__file__).resolve().parents[3]
OUT = ROOT / "output/pdf/TACLOUD_Test_Result_Summary.pdf"
QA = Path("/private/tmp/citra_qa.csv")

pdfmetrics.registerFont(TTFont("Arial", "/System/Library/Fonts/Supplemental/Arial.ttf"))
pdfmetrics.registerFont(TTFont("Arial-Bold", "/System/Library/Fonts/Supplemental/Arial Bold.ttf"))

GREEN = colors.HexColor("#16794A")
GREEN_BG = colors.HexColor("#EAF7F0")
NAVY = colors.HexColor("#183153")
BLUE = colors.HexColor("#2F6FED")
BLUE_BG = colors.HexColor("#EDF3FF")
AMBER = colors.HexColor("#9A6700")
AMBER_BG = colors.HexColor("#FFF7D6")
INK = colors.HexColor("#243447")
MUTED = colors.HexColor("#5F6F7F")
LINE = colors.HexColor("#D8E0E8")
WHITE = colors.white

styles = getSampleStyleSheet()
TITLE = ParagraphStyle("Title", fontName="Arial-Bold", fontSize=24, leading=28, textColor=NAVY, spaceAfter=6)
SUB = ParagraphStyle("Sub", fontName="Arial", fontSize=10, leading=14, textColor=MUTED)
H1 = ParagraphStyle("H1", fontName="Arial-Bold", fontSize=15, leading=19, textColor=NAVY, spaceBefore=4, spaceAfter=8)
H2 = ParagraphStyle("H2", fontName="Arial-Bold", fontSize=11, leading=14, textColor=NAVY, spaceBefore=3, spaceAfter=5)
BODY = ParagraphStyle("Body", fontName="Arial", fontSize=8.4, leading=11.2, textColor=INK)
SMALL = ParagraphStyle("Small", fontName="Arial", fontSize=7.2, leading=9.2, textColor=INK)
TINY = ParagraphStyle("Tiny", fontName="Arial", fontSize=6.4, leading=8.0, textColor=INK)
TH = ParagraphStyle("TH", fontName="Arial-Bold", fontSize=7.3, leading=9, textColor=WHITE)
PASS = ParagraphStyle("Pass", fontName="Arial-Bold", fontSize=7.5, leading=9, textColor=GREEN, alignment=TA_CENTER)
NOTE = ParagraphStyle("Note", fontName="Arial", fontSize=7, leading=9, textColor=AMBER)

campaign = [
    ("1", "Test environment and seed data", "Migration, seed, auth checks"),
    ("2", "Baseline application suite and build", "Laravel tests, Blade/routes, Vite"),
    ("3", "TestSprite browser infrastructure", "Five public-library browser cases"),
    ("4", "Authentication and session security", "Login, logout, throttle, Google domain"),
    ("5", "Role-based authorization", "Cross-role and middleware matrix"),
    ("6", "Ownership and IDOR protection", "Dosen guidance ownership"),
    ("7", "CSRF protection", "Tokenless writes rejected"),
    ("8", "Validation and mass assignment", "Forged privileged fields rejected"),
    ("9", "Upload and download security", "MIME, owner, phase and path"),
    ("10", "XSS and output escaping", "Stored, reflected and DOM sinks"),
    ("11", "Query and sort security", "Sort allow-list and safe search"),
    ("12", "Browser security headers", "nosniff, frame and referrer"),
    ("13", "Mahasiswa workflow", "Proposal, grades, final submission"),
    ("14", "Dosen workflow", "Guidance, grading, sidang request"),
    ("15", "Kaprodi workflow", "Review, completion, historical archive"),
    ("16", "Cross-role lifecycle and library", "Completion appears in library"),
    ("17", "Notifications and privacy", "Recipients and access control"),
    ("18", "Accessibility and modal keyboard use", "Dialog, focus, Tab and Escape"),
    ("19", "Production routes and cache", "Unique routes, cache and optimize"),
    ("20", "Dependencies and release gates", "Audits, build and full suite"),
]

role_map = {"1": "Kaprodi", "2": "Dosen", "3": "Mahasiswa", "4": "General"}
qa_rows = []
with QA.open(encoding="utf-8-sig") as fh:
    for row in csv.DictReader(fh):
        no = (row.get("No") or "").strip()
        if re.fullmatch(r"\d+\.\d+", no):
            role = role_map.get(no.split(".")[0], "-")
            qa_rows.append({
                "id": no,
                "role": role,
                "area": (row.get("Feature Area") or "").strip(),
                "task": (row.get("Task Description") or "").strip(),
                "status": (row.get("Status") or "").strip(),
                "remark": bool((row.get("Notes") or "").strip() or (row.get(None) or [])),
            })

def p(text, style=BODY):
    return Paragraph(str(text), style)

def check_drawing(size=8):
    drawing = Drawing(size, size)
    mark = ShapePath()
    mark.moveTo(size * 0.08, size * 0.48)
    mark.lineTo(size * 0.36, size * 0.18)
    mark.lineTo(size * 0.92, size * 0.84)
    mark.strokeColor = GREEN
    mark.strokeWidth = max(1.4, size * 0.14)
    mark.fillColor = None
    drawing.add(mark)
    return drawing

def pass_cell(text, style=PASS):
    result = Table([[check_drawing(7), p(text, style)]], colWidths=[9, None])
    result.setStyle(TableStyle([
        ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
        ("LEFTPADDING", (0, 0), (-1, -1), 0),
        ("RIGHTPADDING", (0, 0), (-1, -1), 0),
        ("TOPPADDING", (0, 0), (-1, -1), 0),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 0),
    ]))
    return result

def table(data, widths, header=True, font=SMALL, repeats=1):
    cooked = []
    for r, row in enumerate(data):
        cooked.append([cell if hasattr(cell, "wrap") else p(cell, TH if header and r == 0 else font) for cell in row])
    t = Table(cooked, colWidths=widths, repeatRows=repeats if header else 0, hAlign="LEFT")
    commands = [
        ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
        ("GRID", (0, 0), (-1, -1), 0.35, LINE),
        ("LEFTPADDING", (0, 0), (-1, -1), 5),
        ("RIGHTPADDING", (0, 0), (-1, -1), 5),
        ("TOPPADDING", (0, 0), (-1, -1), 4),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 4),
    ]
    if header:
        commands += [("BACKGROUND", (0, 0), (-1, 0), NAVY)]
    for r in range(1 if header else 0, len(data)):
        if r % 2 == 0:
            commands.append(("BACKGROUND", (0, r), (-1, r), colors.HexColor("#F7F9FB")))
    t.setStyle(TableStyle(commands))
    return t

def page(canvas, doc):
    canvas.saveState()
    canvas.setStrokeColor(LINE)
    canvas.line(16 * mm, 12 * mm, 281 * mm, 12 * mm)
    canvas.setFont("Arial", 7)
    canvas.setFillColor(MUTED)
    canvas.drawString(16 * mm, 7.5 * mm, "TACLOUD - Test Result Summary")
    canvas.drawRightString(281 * mm, 7.5 * mm, f"Page {doc.page}")
    canvas.restoreState()

OUT.parent.mkdir(parents=True, exist_ok=True)
doc = BaseDocTemplate(
    str(OUT), pagesize=landscape(A4),
    leftMargin=16 * mm, rightMargin=16 * mm, topMargin=14 * mm, bottomMargin=17 * mm,
    title="TACLOUD Test Result Summary", author="TACLOUD QA"
)
frame = Frame(doc.leftMargin, doc.bottomMargin, doc.width, doc.height, id="normal")
doc.addPageTemplates(PageTemplate(id="main", frames=[frame], onPage=page))

story = [
    p("TACLOUD Test Result Summary", TITLE),
    p("Launch-readiness evidence - 20 July 2026", SUB),
    Spacer(1, 7 * mm),
]

cards = [
    ("20/20", "Final campaign", "All scenarios closed"),
    ("133", "Automated tests", "452 assertions"),
    ("7/7", "Accepted browser runs", "5 library + 2 sidang"),
    ("38/38", "Citra manual QA", "Recorded Passed"),
]
card_table = Table([
    [p(a, ParagraphStyle("Metric", parent=TITLE, fontSize=20, leading=22, textColor=GREEN, alignment=TA_CENTER)) for a, _, _ in cards],
    [p(b, ParagraphStyle("MetricLabel", parent=H2, alignment=TA_CENTER)) for _, b, _ in cards],
    [p(c, ParagraphStyle("MetricSub", parent=SMALL, alignment=TA_CENTER, textColor=MUTED)) for _, _, c in cards],
], colWidths=[doc.width / 4] * 4)
card_table.setStyle(TableStyle([
    ("BOX", (0, 0), (-1, -1), 0.5, LINE),
    ("INNERGRID", (0, 0), (-1, -1), 0.5, LINE),
    ("BACKGROUND", (0, 0), (-1, -1), GREEN_BG),
    ("TOPPADDING", (0, 0), (-1, 0), 12),
    ("BOTTOMPADDING", (0, 2), (-1, 2), 12),
]))
story += [card_table, Spacer(1, 7 * mm), p("Release decision", H1)]
decision = Table([[check_drawing(22),
                   p("<b>READY IN TESTED SCOPE</b><br/>No open blocker remains in the verified authentication, role, sidang-request, grading, final-document, completion, migration, or library flows.", BODY)]],
                 colWidths=[20 * mm, doc.width - 20 * mm])
decision.setStyle(TableStyle([
    ("BOX", (0, 0), (-1, -1), 0.8, GREEN),
    ("BACKGROUND", (0, 0), (-1, -1), GREEN_BG),
    ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
    ("TOPPADDING", (0, 0), (-1, -1), 10),
    ("BOTTOMPADDING", (0, 0), (-1, -1), 10),
]))
story += [decision, Spacer(1, 5 * mm), p("Important reading note", H2),
          p("Citra recorded every row as Passed. Eleven rows also contain remarks from the original manual session. Later patches and automated tests cover the corrected paths, but an identical Citra retest is not documented for every remark.", BODY)]

story += [PageBreak(), p("Final Campaign - 20 Scenarios", TITLE),
          p("The campaign name includes TestSprite, but methods also include Laravel/Pest, security probes, build, route cache, accessibility and dependency audits.", SUB),
          Spacer(1, 4 * mm)]
camp_data = [["#", "Scenario", "Main evidence", "Result"]]
for n, name, evidence in campaign:
    camp_data.append([n, name, evidence, pass_cell("PASS")])
story.append(table(camp_data, [12 * mm, 88 * mm, 125 * mm, 36 * mm], font=SMALL))

story += [PageBreak(), p("TestSprite Browser Results", TITLE), Spacer(1, 3 * mm)]
browser = [
    ["Browser group", "Exact coverage", "Result", "Evidence note"],
    ["Public library", "Five browse, detail and navigation cases", pass_cell("5/5 PASS"), "Accepted browser assertions"],
    ["Dosen sidang cancellation", "Cancel pending request and submit again", pass_cell("1/1 PASS"), "Flash text not asserted; Laravel covers it"],
    ["Two-advisor sidang request", "Both advisors submit; Kaprodi approves; phase advances", pass_cell("1/1 PASS"), "Database confirms outcome; Laravel covers exact phases"],
    ["Old final-approval TC023", "Removed Dosen-approval workflow", "N/A", "Excluded after business-flow correction"],
]
story += [table(browser, [48 * mm, 93 * mm, 36 * mm, 84 * mm], font=SMALL), Spacer(1, 7 * mm),
          p("Current automated evidence", H1)]
release = [
    ["Check", "Result"],
    ["Full Laravel suite", pass_cell("133 tests / 452 assertions")],
    ["Focused final workflow", pass_cell("22 tests / 90 assertions")],
    ["Dependency audit", pass_cell("Composer and npm clean")],
    ["Frontend build", pass_cell("PASS")],
    ["Route list, cache and optimize", pass_cell("PASS")],
    ["Release migration", pass_cell("Ran; obsolete table removed")],
    ["Whitespace check", pass_cell("PASS")],
]
story.append(table(release, [105 * mm, 156 * mm], font=SMALL))

summary_counts = [("Kaprodi", 19), ("Dosen", 7), ("Mahasiswa", 7), ("General", 5)]
story += [PageBreak(), p("Citra Manual QA - 38/38 Passed", TITLE),
          p("Public Google Sheet read on 18 July 2026.", SUB), Spacer(1, 4 * mm)]
sum_data = [["Role", "Passed", "Failed"]] + [[r, pass_cell(str(c)), p("0", PASS)] for r, c in summary_counts] + [["Total", pass_cell("38"), p("0", PASS)]]
story += [table(sum_data, [90 * mm, 85 * mm, 85 * mm], font=SMALL), Spacer(1, 7 * mm),
          p("Manual detail", H1)]

qa_data = [["ID", "Role", "Area", "Short check", "Result"]]
for item in qa_rows:
    task = item["task"]
    if len(task) > 82:
        task = task[:79].rstrip() + "..."
    result = "PASS*" if item["remark"] else "PASS"
    qa_data.append([item["id"], item["role"], item["area"], task, pass_cell(result)])
story.append(table(qa_data, [17 * mm, 25 * mm, 51 * mm, 132 * mm, 36 * mm], font=TINY))
story += [Spacer(1, 3 * mm), p("* A remark was recorded in the original QA row. Later technical verification covers the corrected path; identical manual retest evidence may be absent.", NOTE)]

story += [PageBreak(), p("Evidence and Interpretation", TITLE),
          Spacer(1, 3 * mm),
          p("What PASS means", H1),
          p("A PASS is supported by the named method. Browser cases are separated from Laravel/Pest and other technical checks; the report does not claim that all 20 scenarios were browser tests.", BODY),
          Spacer(1, 4 * mm),
          p("Known non-blocking evidence limits", H1)]
limits = [
    ["Item", "Meaning"],
    ["TestSprite assertion quality", "Some generated scripts used weaker final assertions. Exact server-side state transitions are covered by Laravel tests and database verification."],
    ["Citra remarks", "The sheet records Passed, but 11 rows include observations. Later fixes are technically verified; not every identical manual step was repeated by Citra."],
    ["Deployment configuration", "HTTPS, secure cookies, HSTS, backup and post-deploy smoke tests remain operational deployment checks."],
]
story += [table(limits, [68 * mm, 193 * mm], font=SMALL), Spacer(1, 7 * mm),
          p("Sources", H1),
          p("Citra Manual QA public Google Sheet; TestSprite dashboards and generated scripts; current Laravel/Pest output; TestSprite_Final_Campaign_Evidence.md; live migration verification.", BODY)]

doc.build(story)
print(OUT)

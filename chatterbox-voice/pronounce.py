"""Script text -> text the voice should read. Shared by the tower server and the PC scripts.

Guide file (PRONUNCIATION_FILE, default /models/pronunciation.txt), one rule per line:
    Mereak Firmaxe = Maryak Firmax
    # comments and blank lines ignored; longer phrases are applied first; case-insensitive whole words.
Re-read whenever the file changes, so edits apply on the next request with no restart.
Then every number is written out as words ("559" -> "five hundred fifty-nine").
"""
import os, re

ONES = "zero one two three four five six seven eight nine ten eleven twelve thirteen fourteen fifteen sixteen seventeen eighteen nineteen".split()
TENS = "_ _ twenty thirty forty fifty sixty seventy eighty ninety".split()
ORD = {"one": "first", "two": "second", "three": "third", "five": "fifth", "eight": "eighth", "nine": "ninth", "twelve": "twelfth"}


def cardinal(n):
    if n < 20: return ONES[n]
    if n < 100: return TENS[n // 10] + ("-" + ONES[n % 10] if n % 10 else "")
    if n < 1000: return ONES[n // 100] + " hundred" + (" " + cardinal(n % 100) if n % 100 else "")
    if n < 1_000_000: return cardinal(n // 1000) + " thousand" + (" " + cardinal(n % 1000) if n % 1000 else "")
    if n < 1_000_000_000: return cardinal(n // 1_000_000) + " million" + (" " + cardinal(n % 1_000_000) if n % 1_000_000 else "")
    return " ".join(ONES[int(d)] for d in str(n))


def ordinal(n):
    w = cardinal(n); sep = "-" if "-" in w else " "
    head, _, last = w.rpartition(sep)
    last = ORD.get(last, (last[:-1] + "ieth") if last.endswith("y") else last + "th")
    return (head + sep if head else "") + last


def year(n):  # 1977 -> nineteen seventy-seven, 2005 -> two thousand five
    if 2000 <= n < 2010: return cardinal(n)
    r = n % 100
    return cardinal(n // 100) + " " + (cardinal(r) if r >= 10 else "oh " + cardinal(r) if r else "hundred")


def numbers(t):
    t = re.sub(r"\b(\d{1,3}(?:,\d{3})+)\b", lambda m: m[1].replace(",", ""), t)  # 10,000 -> 10000
    t = re.sub(r"\$(\d+)", lambda m: cardinal(int(m[1])) + " dollars", t)
    t = re.sub(r"(\d+)%", lambda m: cardinal(int(m[1])) + " percent", t)
    t = re.sub(r"\b(\d+)(st|nd|rd|th)\b", lambda m: ordinal(int(m[1])), t)
    t = re.sub(r"\b(\d+)\.0\b", lambda m: cardinal(int(m[1])) + " point oh", t)
    t = re.sub(r"\b(\d+)\.(\d+)\b", lambda m: cardinal(int(m[1])) + " point " + " ".join(ONES[int(d)] for d in m[2]), t)
    t = re.sub(r"\b(1[89]\d\d|20[1-4]\d)\b", lambda m: year(int(m[1])), t)  # ponytail: 1800-2049 read as years
    t = re.sub(r"([A-Za-z])(\d+)\b", lambda m: m[1] + " " + cardinal(int(m[2])), t)  # Commlink6
    return re.sub(r"\d+", lambda m: cardinal(int(m[0])), t)


def parse(text):
    rules = []
    for line in text.splitlines():
        line = line.split("#", 1)[0].strip()
        if "=" in line:
            a, b = (s.strip() for s in line.split("=", 1))
            if a: rules.append((a, b))
    return sorted(rules, key=lambda r: -len(r[0]))


_cache = {"path": None, "mtime": None, "rules": []}


def rules(path=None):
    path = path or os.environ.get("PRONUNCIATION_FILE", "/models/pronunciation.txt")
    try:
        m = os.path.getmtime(path)
    except OSError:
        return []
    if (_cache["path"], _cache["mtime"]) != (path, m):
        with open(path, encoding="utf-8-sig") as f:
            _cache.update(path=path, mtime=m, rules=parse(f.read()))
    return _cache["rules"]


def spoken(text, rule_list=None):
    text = text.replace("(...)", ",")
    for a, b in (rules() if rule_list is None else rule_list):
        text = re.sub(r"(?<!\w)" + re.escape(a) + r"(?!\w)", b, text, flags=re.I)
    return numbers(text)


def _check():
    r = parse("Mereak Firmaxe = Maryak Firmax\nMereak = Maryak  # first name\nnuyen = new yen\nSINs = sins\n")
    assert spoken("Mereak Firmaxe and Mereak's 10,000 nuyen on SINs (...) ok", r) == \
        "Maryak Firmax and Maryak's ten thousand new yen on sins , ok"
    assert numbers("559 amps, 6th, $11, 50%, Anarchy 2.0, 1977, Commlink6") == \
        "five hundred fifty-nine amps, sixth, eleven dollars, fifty percent, Anarchy two point oh, nineteen seventy-seven, Commlink six"
    assert numbers("2,500,000 and 3.5") == "two million five hundred thousand and three point five"


if __name__ == "__main__":
    import sys
    _check()
    print(spoken(sys.argv[1]) if len(sys.argv) > 1 else "CHECK_OK")

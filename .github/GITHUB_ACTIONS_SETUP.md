# 🚀 GitHub Actions - Automatyczne Tłumaczenia

## ✅ Setup Instructions

### Krok 1: Włącz GitHub Actions

1. Wejdź na GitHub: `https://github.com/YOUR_REPO`
2. **Settings** → **Actions** → **General**
3. Zaznacz: "Allow all actions and reusable workflows"

### Krok 2: Skonfiguruj Crowdin (Optional ale Polecane!)

#### 2a. Załóż konto Crowdin
- Wejdź: https://crowdin.com
- Zarejestruj się (darmowe dla open-source)
- Utwórz projekt "yap-plugin"

#### 2b. Pobierz credentials
1. **Settings** → **API** → **Personal access tokens**
2. Kliknij "New token"
3. Zaznacz: `projects`, `source_strings`, `translations`
4. Skopiuj token

#### 2c. Dodaj GitHub Secrets
1. Na GitHub: **Settings** → **Secrets and variables** → **Actions**
2. Kliknij **New repository secret**

Dodaj 3 secrety:

```
Name: CROWDIN_PERSONAL_TOKEN
Value: (token z Crowdin)

Name: CROWDIN_PROJECT_ID
Value: (Project ID z Crowdin - np. 123456)

Name: GITHUB_TOKEN
Value: (auto - GitHub dostarcza, nie musisz nic robić)
```

### Krok 3: Trigger Workflow

Workflow uruchamia się automatycznie na:
- ✅ **Push do main/develop** (zmiany w `.php`)
- ✅ **Pull Request do main**
- ✅ **Ręczne** - Actions tab → Trigger manually

---

## 🔄 Workflow Diagram

```
Developer pushes code
         ↓
[1] Generate .pot from PHP ✅
         ↓
[2] Upload .pot to Crowdin 🔄
         ↓
Crowdin: Translatorzy tłumaczą 👥
         ↓
[3] Download .po z Crowdin 📥
         ↓
[4] Compile .po → .mo 🔨
         ↓
[5] Commit .mo files 📦
         ↓
Plugin supports all languages! 🌍
```

---

## 📊 Jobs w Workflow

### 1️⃣ **generate-pot**
- Uruchamia się zawsze
- `wp i18n make-pot` generuje `yap.pot`
- Auto-commituje zmiany

### 2️⃣ **crowdin-sync**
- Tylko na push do `main`
- Uploaduje `.pot` do Crowdin
- Pobiera nowe tłumaczenia
- Tworzy PR z tłumaczeniami

### 3️⃣ **compile-mo**
- Kompiluje wszystkie `.po` → `.mo`
- `.mo` to binarny format dla WordPress
- Auto-commituje `.mo` pliki

### 4️⃣ **lint-translations**
- Waliduje składnię `.po`
- Raportuje % tłumaczenia
- Fails jeśli są błędy

---

## 📝 Pliki w Workflow

### Repozytoriowe
```
.github/
└── workflows/
    └── translations.yml      ← GitHub Actions config

crowdin.yml                    ← Crowdin config
languages/
├── yap.pot                   ← Template (generated)
├── yap-pl_PL.po              ← Polish (edytowalny)
├── yap-pl_PL.mo              ← Polish compiled
├── yap-de_DE.po              ← German (edytowalny)
└── yap-de_DE.mo              ← German compiled
```

### GitHub Secrets (nigdy nie commituj!)
```
CROWDIN_PERSONAL_TOKEN  (🔐 Secret)
CROWDIN_PROJECT_ID      (🔐 Secret)
GITHUB_TOKEN            (auto)
```

---

## 🎯 Workflow Ścieżka

### Bez Crowdin (tylko local)
```
Edytujesz .po w Poedicie
         ↓
Commituje do GitHub
         ↓
GitHub Actions:
  - Generuje .pot ✅
  - Kompiluje .mo ✅
  - Commituje .mo ✅
```

### Z Crowdin (Rekomendowane!)
```
Edytujesz kod PHP
         ↓
Commituje do GitHub
         ↓
GitHub Actions:
  - Generuje .pot ✅
  - Uploaduje do Crowdin 📤
  - Tłumacze pracują w Crowdin 👥
  - Auto pobiera tłumaczenia 📥
  - Kompiluje .mo ✅
  - Tworzy PR 🔄
  - Commituje .mo ✅
```

---

## 🚨 Troubleshooting

### ❌ Actions nie uruchamiają się
- Sprawdź: **Settings** → **Actions** → włączone?
- Sprawdź: `.github/workflows/translations.yml` istnieje?

### ❌ Crowdin sync fails
- Sprawdź Secrets: **Settings** → **Secrets**
- Upewnij się: `CROWDIN_PROJECT_ID` jest poprawny
- Upewnij się: Token ma permisje `projects`, `source_strings`

### ❌ .mo pliki nie kompilują się
- Sprawdź syntax `.po`: `msgfmt -c yap-pl_PL.po`
- Sprawdź czy WP CLI jest zainstalowany: `wp --version`

### ✅ Jak debugować
1. Wejdź: **Actions** tab na GitHub
2. Kliknij workflow name
3. Ekspanduj kroki - czytaj logi

---

## 🔐 Security Best Practices

### ❌ Nie rób
```
❌ commituj Crowdin token do repo
❌ pushuj private keys
❌ wpisuj credentials w YAML
```

### ✅ Rób
```
✅ użyj GitHub Secrets
✅ rotate tokens co 3 miesiące
✅ limituj token permissions
✅ reviewuj auto-commits
```

---

## 📱 Next Steps

1. **Zainstaluj Crowdin** (jeśli chcesz zespół tłumaczy)
2. **Dodaj Secrets do GitHub**
3. **Push first commit** - workflow powinien się uruchomić
4. Sprawdź **Actions** tab - czy logi są zielone?
5. Invite translators do Crowdin!

---

## 🌍 Supported Languages

Crowdin automatycznie wspiera 100+ języków!

Najczęstsze:
- 🇵🇱 Polski (pl_PL)
- 🇩🇪 Niemiecki (de_DE)
- 🇫🇷 Francuski (fr_FR)
- 🇪🇸 Hiszpański (es_ES)
- 🇬🇧 Angielski UK (en_GB)
- 🇮🇹 Włoski (it_IT)
- 🇯🇵 Japoński (ja_JP)
- 🇨🇳 Chiński (zh_CN)
- 🇵🇹 Portugalski (pt_BR)
- 🇷🇺 Rosyjski (ru_RU)

Dodaj nowe języki w `crowdin.yml`!

---

**Potrzebujesz help? Pytaj!** 🚀

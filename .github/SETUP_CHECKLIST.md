# 📋 GitHub Actions Setup Checklist

## 🎯 Quick Start (5 minut)

### ✅ Option A: Local Translations (Bez Crowdin)
**Best for:** Solo projects, tight budget

- [ ] Repository ma `.github/workflows/translations-local.yml`
- [ ] Push coś do GitHub
- [ ] Sprawdź **Actions** tab - czy job się uruchomił?
- [ ] Sprawdź czy `.mo` pliki są w `languages/`
- ✅ Done! Gotowe.

**Workflow:**
```
Edytujesz .po lokalnie (Poedit)
         ↓
Commituje do GitHub
         ↓
GitHub Actions: .pot + .mo ✅
```

---

### ✅ Option B: Z Crowdin (Polecane!)
**Best for:** Wielojęzyczne, zespółowe projekty

#### Krok 1: Crowdin Setup (5 min)
- [ ] Załóż konto: https://crowdin.com
- [ ] Stwórz projekt "yap-plugin"
- [ ] Skopiuj: **Project ID** (np. 123456)
- [ ] Wygeneruj: **Personal Access Token**
  - Settings → API → New token
  - Zaznacz: `projects`, `source_strings`, `translations`

#### Krok 2: GitHub Secrets (3 min)
1. Wejdź GitHub repo
2. **Settings** → **Secrets and variables** → **Actions**
3. **New repository secret**

```
SECRET 1:
Name: CROWDIN_PROJECT_ID
Value: 123456 (twój Project ID)

SECRET 2:
Name: CROWDIN_PERSONAL_TOKEN
Value: xxxxx... (twój Personal Token)

SECRET 3: (auto)
Name: GITHUB_TOKEN
Value: (GitHub auto-dostarcza, nie rób nic)
```

#### Krok 3: Włącz Workflow
- [ ] Repository ma `.github/workflows/translations.yml` (główny)
- [ ] Push coś do GitHub
- [ ] Sprawdź **Actions** tab

**Workflow:**
```
Push kod → .pot generuje → Upload Crowdin → Translators pracują
                                    ↓
                         Download tłumaczenia ← PR
                                    ↓
                         Compile .mo → commit
```

---

## 🔍 Verification

### Jak sprawdzić czy Actions działa?

1. Wejdź: `https://github.com/YOUR_USER/YOUR_REPO/actions`
2. Powinny być workflow runs (zielone checkmarks ✅)
3. Kliknij ostatni run
4. Otwórz job (np. "translations")
5. Czytaj logi - czy są zielone?

### Co powinno być w repo?

```
✅ .github/
   ├── workflows/
   │   ├── translations.yml (z Crowdin)
   │   └── translations-local.yml (bez Crowdin)
   └── GITHUB_ACTIONS_SETUP.md (instrukcja)

✅ crowdin.yml (config z Crowdin)

✅ languages/
   ├── yap.pot (template - generated)
   ├── yap-pl_PL.po (Polish - edytowalny)
   └── yap-pl_PL.mo (Polish compiled)
```

### Co powinno być w Secrets?

```
✅ CROWDIN_PERSONAL_TOKEN (jeśli używasz Crowdin)
✅ CROWDIN_PROJECT_ID (jeśli używasz Crowdin)
✅ GITHUB_TOKEN (auto - nie musisz nic robić)
```

---

## ⚠️ Common Issues

### ❌ "Actions not running"
**Fix:**
1. Sprawdź czy Actions są włączone: Settings → Actions → Enabled?
2. Sprawdź czy `.yml` file istnieje w `.github/workflows/`
3. Sprawdź czy `.yml` ma prawidłowy YAML syntax (użyj https://www.yamllint.com/)

### ❌ "Crowdin sync fails"
**Fix:**
1. Sprawdź Secrets: Settings → Secrets
2. Upewnij się: `CROWDIN_PROJECT_ID` i `CROWDIN_PERSONAL_TOKEN` istnieją
3. Token ma permisje? Settings → API → sprawdź scopes
4. Project ID jest poprawny? Crowdin → Settings → ID

### ❌ ".mo file not generated"
**Fix:**
1. Sprawdź `.po` syntax: `msgfmt -c yap-pl_PL.po`
2. WP CLI zainstalowany? `wp --version`
3. Logi: Actions tab → otwórz job → czytaj compile step

### ❌ "Git commit fails"
**Fix:**
1. Sprawdź czy GITHUB_TOKEN ma write permissions
2. Sprawdź `.git/config` dla branch rules
3. Disable branch protection tymczasowo (test)

---

## 🚀 Next Steps

### Tier 1: Start Simple (Now)
- [x] Setup local translations workflow
- [x] Test: push change → Actions run
- [x] Verify: `.mo` files generated

### Tier 2: Add Crowdin (Next)
- [ ] Crowdin account
- [ ] Add Secrets
- [ ] Enable main translations.yml
- [ ] Invite translators

### Tier 3: Advanced (Later)
- [ ] Setup CI/CD for tests
- [ ] Add translation coverage badges
- [ ] Integrate with Slack/Discord notifications
- [ ] Auto-release with translations

---

## 📚 Resources

- **WP CLI Docs:** https://developer.wordpress.org/cli/commands/i18n/
- **Crowdin Docs:** https://support.crowdin.com/
- **GitHub Actions:** https://docs.github.com/en/actions
- **i18n Guide:** https://developer.wordpress.org/plugins/internationalization/

---

## ✅ Status

| Feature | Status | Notes |
|---------|--------|-------|
| `.pot` generation | ✅ Ready | Auto on push |
| `.mo` compilation | ✅ Ready | Auto on push |
| Crowdin sync | ⚠️ Manual setup | Need Secrets |
| Polish (pl_PL) | ✅ Ready | Example provided |
| Validation | ✅ Ready | Syntax check |

---

**Need help? Check `.github/GITHUB_ACTIONS_SETUP.md` for detailed guide!** 🚀

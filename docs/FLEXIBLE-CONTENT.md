# YAP Flexible Content - Przewodnik użytkownika

## Czym jest Flexible Content?

Flexible Content to najbardziej zaawansowane pole w YAP Plugin, które pozwala użytkownikom budować dynamiczne layouty ze różnymi typami sekcji. Każda sekcja może mieć własny zestaw pól.

**Idealne zastosowania:**
- Landing pages z różnymi sekcjami
- Strony "O nas" z różnymi blokami treści  
- Page buildery kontrolowane przez developerów
- Flexible homepage layouts

## Jak skonfigurować Flexible Content?

### 1. Dodaj pole Flexible Content w Visual Builder

1. Przejdź do **Visual Builder**
2. Wybierz lub stwórz grupę pól
3. Dodaj nowe pole typu **Flexible Content**
4. Kliknij przycisk **"Zarządzaj layoutami"**

### 2. Stwórz layouty (typy sekcji)

W oknie zarządzania layoutami dodaj różne typy sekcji, np.:

**Hero Section:**
- Nazwa: `hero_section`
- Pola: title (text), subtitle (text), image (image), button_text (text), button_url (url)

**Three Columns:**
- Nazwa: `three_columns`
- Pola: column_1_title, column_1_text, column_2_title, column_2_text, column_3_title, column_3_text

**Testimonials:**
- Nazwa: `testimonials`
- Pola: author_name (text), author_image (image), quote (textarea)

**CTA Banner:**
- Nazwa: `cta_banner`
- Pola: background_color (color), heading (text), subheading (text), button_text (text), button_link (url)

## Jak używać w szablonach?

### Podstawowe użycie

```php
<?php yap_flexible('page_content', 'sections'); ?>
```

To wyświetli wszystkie sekcje używając szablonów z folderu `flexible/` w twoim temacie.

### Tworzenie szablonów dla layoutów

Stwórz folder `flexible/` w swoim temacie:

```
your-theme/
├── flexible/
│   ├── hero_section.php
│   ├── three_columns.php
│   ├── testimonials.php
│   └── cta_banner.php
```

**Przykład: `flexible/hero_section.php`**

```php
<?php
/**
 * Template for Hero Section layout
 * Available variables: $flexible_fields, $flexible_layout
 */

$title = $flexible_fields['title'] ?? '';
$subtitle = $flexible_fields['subtitle'] ?? '';
$image_id = $flexible_fields['image'] ?? '';
$button_text = $flexible_fields['button_text'] ?? '';
$button_url = $flexible_fields['button_url'] ?? '';

$image_url = wp_get_attachment_image_url($image_id, 'full');
?>

<section class="hero-section" style="background-image: url('<?php echo esc_url($image_url); ?>');">
    <div class="container">
        <h1><?php echo esc_html($title); ?></h1>
        <p class="subtitle"><?php echo esc_html($subtitle); ?></p>
        <?php if ($button_text && $button_url): ?>
            <a href="<?php echo esc_url($button_url); ?>" class="btn btn-primary">
                <?php echo esc_html($button_text); ?>
            </a>
        <?php endif; ?>
    </div>
</section>
```

**Przykład: `flexible/three_columns.php`**

```php
<?php
$columns = [];
for ($i = 1; $i <= 3; $i++) {
    $columns[] = [
        'title' => $flexible_fields["column_{$i}_title"] ?? '',
        'text' => $flexible_fields["column_{$i}_text"] ?? ''
    ];
}
?>

<section class="three-columns">
    <div class="container">
        <div class="row">
            <?php foreach ($columns as $column): ?>
                <div class="col-md-4">
                    <h3><?php echo esc_html($column['title']); ?></h3>
                    <p><?php echo esc_html($column['text']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
```

**Przykład: `flexible/testimonials.php`**

```php
<?php
$author_name = $flexible_fields['author_name'] ?? '';
$author_image = $flexible_fields['author_image'] ?? '';
$quote = $flexible_fields['quote'] ?? '';

$author_image_url = wp_get_attachment_image_url($author_image, 'thumbnail');
?>

<section class="testimonial">
    <div class="container">
        <blockquote>
            <p>"<?php echo esc_html($quote); ?>"</p>
            <footer>
                <?php if ($author_image_url): ?>
                    <img src="<?php echo esc_url($author_image_url); ?>" alt="<?php echo esc_attr($author_name); ?>" class="testimonial-avatar">
                <?php endif; ?>
                <cite><?php echo esc_html($author_name); ?></cite>
            </footer>
        </blockquote>
    </div>
</section>
```

### Zaawansowane użycie - pętla z kontrolą

```php
<?php
$sections = yap_get_flexible('page_content', 'sections');

if (!empty($sections)) {
    foreach ($sections as $section) {
        $layout = $section['layout'];
        $fields = $section['fields'];
        
        // Custom rendering based on layout type
        switch ($layout) {
            case 'hero_section':
                // Custom hero rendering
                echo '<div class="hero-custom">';
                echo '<h1>' . esc_html($fields['title']) . '</h1>';
                echo '</div>';
                break;
                
            case 'three_columns':
                // Use template
                set_query_var('flexible_fields', $fields);
                get_template_part('flexible/three_columns');
                break;
                
            default:
                // Generic rendering
                echo '<pre>' . print_r($fields, true) . '</pre>';
        }
    }
}
?>
```

### Dostęp do konkretnej sekcji

```php
<?php
$sections = yap_get_flexible('page_content', 'sections');

// Get first section
$first_section = $sections[0] ?? null;
if ($first_section) {
    echo $first_section['fields']['title'];
}

// Get all sections of specific type
$hero_sections = array_filter($sections, function($section) {
    return $section['layout'] === 'hero_section';
});
?>
```

## Najlepsze praktyki

### 1. Nazywanie layoutów

✅ **Dobre:**
- `hero_section`
- `three_columns`
- `testimonial_slider`
- `cta_banner`

❌ **Złe:**
- `Section1` (brak kontekstu)
- `hero section` (spacje)
- `HeroSection` (wielkie litery)

### 2. Struktura pól w layoutach

Używaj czytelnych nazw pól:
```
hero_title (nie: ht, title1)
button_text (nie: btn, bt)
background_image (nie: bg, img)
```

### 3. Fallback gdy brak szablonu

Jeśli nie ma szablonu `flexible/{layout}.php`, YAP wyświetli strukturę w `<pre>`:

```html
<div class="flexible-section flexible-hero_section">
    <h3>Hero Section</h3>
    <pre>
    Array(
        [title] => Welcome
        [subtitle] => To our site
        ...
    )
    </pre>
</div>
```

To pozwala zobaczyć dostępne dane podczas development.

### 4. Style CSS

Dodaj base styles dla wszystkich layoutów:

```css
.flexible-section {
    margin: 40px 0;
    padding: 60px 0;
}

.hero-section {
    background-size: cover;
    background-position: center;
    min-height: 500px;
}

.three-columns .row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
}
```

## Porównanie z ACF PRO

| Funkcja | ACF PRO | YAP Plugin |
|---------|---------|-----------|
| Dodawanie layoutów | ✅ | ✅ |
| Drag & drop sekcji | ✅ | ✅ |
| Duplikowanie sekcji | ✅ | ✅ |
| Zagnieżdżone flexible | ✅ | ❌ (planned) |
| Conditional logic | ✅ | ⚠️ (basic) |
| Clone layouts | ✅ | 🔜 (planned) |
| Min/Max sekcji | ✅ | ✅ |

## Przykłady rzeczywistych zastosowań

### Landing Page Builder

```php
<?php
// Template: page-landing.php
get_header('landing');

yap_flexible('landing_page', 'sections');

get_footer('landing');
?>
```

### Homepage z modułami

```php
<?php
// Template: front-page.php
get_header();

$sections = yap_get_flexible('homepage', 'hero_sections');

// Always show hero first
if (!empty($sections)) {
    $hero = array_shift($sections);
    set_query_var('flexible_fields', $hero['fields']);
    get_template_part('flexible/hero_section');
}

// Then show other sections
foreach ($sections as $section) {
    set_query_var('flexible_fields', $section['fields']);
    set_query_var('flexible_layout', $section['layout']);
    get_template_part('flexible/' . $section['layout']);
}

get_footer();
?>
```

### Custom post type z flexible content

```php
<?php
// Single event template
get_header();

while (have_posts()) {
    the_post();
    
    echo '<h1>' . get_the_title() . '</h1>';
    echo '<div class="event-meta">' . get_the_date() . '</div>';
    
    // Flexible content for event details
    yap_flexible('events', 'event_sections');
}

get_footer();
?>
```

## Debugowanie

### Wyświetl surowe dane

```php
<?php
$sections = yap_get_flexible('page_content', 'sections');
echo '<pre>';
print_r($sections);
echo '</pre>';
?>
```

### Sprawdź dostępne layouty

```php
<?php
$layouts = YAP_Flexible_Content::get_layouts('page_content', 'sections');
echo '<pre>';
print_r($layouts);
echo '</pre>';
?>
```

## FAQ

**Q: Czy mogę zagnieździć Flexible Content w Repeaterze?**  
A: Nie, Flexible Content może być tylko na głównym poziomie grupy.

**Q: Jak ograniczyć liczbę sekcji?**  
A: W konfiguracji layoutu ustaw `min` i `max` wartości.

**Q: Czy mogę eksportować/importować layouty?**  
A: Tak, używaj JSON Manager w YAP Plugin.

**Q: Jak zmienić kolejność layoutów?**  
A: W modal "Zarządzaj layoutami" przeciągnij layouty za pomocą ikony ≡

**Q: Czy działa z Gutenbergiem?**  
A: Tak, Flexible Content działa z klasycznym editorem i Gutenbergiem (jako metabox).

## Support

Problemy? Pytania? Sprawdź:
- GitHub Issues: https://github.com/KacperBB/YAP-YetAnotherPlugin
- Documentation: Plugin folder `/docs`

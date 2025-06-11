# Modern Frontpage Theme

Modern Frontpage, Moodle için Tailwind CSS kullanılarak geliştirilmiş modern bir temadır.

## Özellikler

- **Modern Tasarım**: Tailwind CSS ile şık ve modern bir arayüz
- **Responsive Tasarım**: Tüm cihazlarda mükemmel görünüm
- **Hero Section**: Etkileyici ana sayfa hero bölümü
- **Özellik Kartları**: Modern özellik kartları ile bilgi sunumu
- **Gradient Arkaplanlar**: Görsel olarak çekici gradient arkaplanlar
- **Hover Efektleri**: İnteraktif hover animasyonları

## Kurulum

1. Bu temayı Moodle'ın `theme/` dizinine kopyalayın
2. Admin panelinden tema ayarlarına gidin
3. "Modern Frontpage" temasını seçin
4. Cache'i temizleyin

## Özelleştirme

### Renkler
Tema ayarlarından brand color'ı değiştirebilirsiniz.

### SCSS
Tema ayarlarından özel SCSS kodları ekleyebilirsiniz.

## Teknik Detaylar

- **Ana Tema**: Boost tema üzerine kurulmuştur
- **CSS Framework**: Tailwind CSS
- **Responsive**: Mobile-first tasarım yaklaşımı
- **Browser Support**: Tüm modern tarayıcılar

## Geliştirici Notları

Bu tema üniversite projesi kapsamında geliştirilmiştir ve öğrenme amaçlıdır.

### Dosya Yapısı

```
theme/modernfrontpage/
├── config.php           # Tema konfigürasyonu
├── version.php          # Tema versiyonu
├── lib.php              # Tema fonksiyonları
├── settings.php         # Tema ayarları
├── layout/              # Layout dosyaları
│   ├── frontpage.php    # Ana sayfa layout'u
│   └── drawers.php      # Diğer sayfalar için layout
├── templates/           # Mustache template'leri
│   └── frontpage.mustache
├── scss/                # SCSS dosyaları
│   ├── pre.scss
│   └── post.scss
├── style/               # Derlenmiş CSS
│   └── moodle.css
└── lang/                # Dil dosyaları
    ├── en/
    └── tr/
```

## Lisans

GPL v3 veya üzeri 
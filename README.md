# Poyraz XML Import (Magento 2 Modülü)

Magento 2 için geliştirilen bu modül, çoklu XML kaynaklarından ürün verilerini okuyup katalogda ürünleri oluşturur ya da günceller. Kaynak yönetimi ve mapping tamamen **admin panelinde JSON alanlarıyla** yönetilir; ek tablo veya dosya yapısı gerektirmez.

## Temel Özellikler
- Birden fazla XML kaynağını JSON ile tanımlayabilme (kod, ad, URL, encoding, aktiflik, frekans, e‑posta, para birimi, kur, marj vb.).
- Kaynağa özel XPath tabanlı mapping ve dönüştürücüler ile esnek alan eşlemesi.
- Kategori, fiyat, stok, resim ve attribute import desteği.
- CLI komutlarıyla manuel tetikleme; cron ile otomatik çalıştırma.
- `/var/log/poyraz_xml.log` altında özel log dosyası ve ayarlanabilir log seviyesi.

## Gereksinimler
- Magento 2.4+ kurulumu.
- PHP 8.x.
- İlgili ortamda internet erişimi (XML URL’lerini indirebilmek için).

## Kurulum
1. Modül klasörünü `app/code/Poyraz/XmlImport` altında konumlandırın.
2. Magento kurulum dizininde aşağıdaki komutları çalıştırın:
   ```bash
   bin/magento module:enable Poyraz_XmlImport
   bin/magento setup:upgrade
   bin/magento setup:di:compile
   bin/magento cache:flush
   ```

## Yönetim Paneli Ayarları
`Stores > Configuration > Poyraz > XML Import` altında dört ana grup bulunur:

### General Settings
- **Enable Module:** Modülün genel açık/kapalı durumu.
- **Default Attribute Set ID:** Ürün oluştururken kullanılacak öntanımlı attribute set ID’si.
- **Default Website Code:** Kaynak belirtmezse ürünlerin atanacağı website kodu.
- **Default Stock Status:** Stok bilgisi gelmediğinde kullanılacak varsayılan durum.
- **Target Currency / TRY to USD / EUR to USD:** Para birimi dönüştürme oranları.
- **Global Margin Percentage:** Fiyatlara uygulanacak genel marj (örn. 20 → 1.20 çarpanı).

### XML Sources
- **Sources JSON:** Her bir kaynağı `code`, `name`, `url`, `encoding`, `active`, `frequency`, `email`, `currency`, `rate`, `margin` gibi anahtarlarla tanımlayan JSON dizesi. Örnek birden çok kaynak içeren bir dizi yapısı kullanılabilir.

### Mapping
- **Mapping JSON:** Kaynak kodunu anahtar olarak kullanan, XPath’lerle alan eşleme ve varsa dönüştürücü fonksiyonları içeren JSON. Kategori, resim, stok, fiyat ve attribute eşlemeleri bu yapı üzerinden yönetilir.

### Advanced
- **Log Level:** Log seviyesi (INFO, DEBUG vb.).
- **Import Image Path:** `pub/media` altında resimlerin indirileceği göreli klasör.

## Komut Satırı Kullanımı
- **Belirli kaynak için import:**
  ```bash
  bin/magento poyraz:xml:import <source_code>
  ```
  `source_code` sistem ayarlarında tanımlanan kaynağın koduyla eşleşmelidir.

- **Tüm aktif kaynaklar için import:**
  ```bash
  bin/magento poyraz:xml:import:all
  ```

## Cron
Modül varsayılan olarak her 30 dakikada bir `poyraz_xml_import_cron` job’ını çalıştırarak tüm aktif kaynakları işler. İhtiyaç halinde Magento cron planlayıcısı üzerinden zamanlama güncellenebilir.

## Loglama
Import işlemleri `/var/log/poyraz_xml.log` dosyasına yazılır. Log seviyesi yönetim panelindeki **Advanced > Log Level** alanıyla belirlenir.

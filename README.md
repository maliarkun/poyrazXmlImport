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
`Stores > Configuration > Poyraz > XML Import` artık Prestashop’taki “Easy Import” akışına benzer üç adımlı bir rehber sunar:

### Step 1: General Import Rules
- **Enable module:** Modülün tamamını açıp kapatır; kapalıysa cron ve CLI tetikleri çalışmaz.
- **Default attribute set / website / stock status:** Yeni ürünler için temel değerler.

### Step 2: Sources & Scheduling
- **Sources JSON:** Her kaynak için `code`, `name`, `url`, `active`, `currency`, `rate`, `margin`, `default_attribute_set`, `image_path`, `frequency` vb. alanları içeren liste. Tooltip içinde hazır bir şablon bulunur.
- **Default cron frequency:** Kaynak bazında frekans belirtilmediyse kullanılacak zamanlama.

### Step 3: Mapping & Field Guidance
- **Mapping JSON:** Kaynak kodunu anahtar olarak kullanır. `product_node` ile satır XPath’i, `fields` ile tekil alan eşlemesi, `arrays` ile çoklu değerleri tanımlarsınız. Tooltip örneği stok, fiyat, kategori ve görsel eşleşmelerini gösterir.

### Advanced & Logging
- **Base image path:** Resimlerin `pub/media` altındaki hedef dizini.
- **Log level:** Ayrıntı seviyesi.

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

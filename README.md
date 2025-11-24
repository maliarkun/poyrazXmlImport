# Poyraz XML Import (Magento 2 Module)

Magento 2 için geliştirilmiş esnek XML ürün import modülü.

Bu modül, birden çok XML kaynağından ürünleri okur, işler ve Magento kataloguna aktarır.  
Kaynak tanımları ve XML mapping işlemleri tamamen **admin panelinden yönetilen JSON yapılarına** dayanır.  
Modül dosya yapısında veya veritabanında ek tablo gerektirmez.

---

## Özellikler

- Birden fazla XML kaynağı tanımlayabilme
- Her kaynak için ayrı:
  - URL
  - encoding
  - para birimi
  - kur oranı
  - marj değerleri
  - aktif/pasif durumu
- JSON tabanlı esnek mapping yapısı
- CLI komutları ile manuel import
- Cron üzerinden otomatik import
- Özel log dosyası kullanımı
- Kategori, fiyat, stok, resim ve attribute import işlemleri

---

## Kurulum

Modülü aşağıdaki dizine yerleştirin:

Ardından Magento'ya modülü tanıtın:

#
bin/magento module:enable Poyraz_XmlImport
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
#

PHP 8.x ile sorunsuz çalışır.

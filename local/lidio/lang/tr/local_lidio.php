<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Language strings for the Lidio payment system plugin.
 *
 * @package    local_lidio
 * @copyright  2023 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Lidio Ödeme Sistemi';
$string['plugindisabled'] = 'Lidio ödeme sistemi eklentisi şu anda devre dışı.';

// Capabilities
$string['lidio:manageplugin'] = 'Lidio eklenti ayarlarını yönet';
$string['lidio:bemerchant'] = 'Lidio satıcısı olmak için başvur';
$string['lidio:managemerchants'] = 'Lidio satıcılarını yönet';
$string['lidio:viewmerchants'] = 'Lidio satıcılarını görüntüle';

// Settings
$string['settings'] = 'Lidio ayarları';
$string['enabled'] = 'Lidio\'yu etkinleştir';
$string['enabled_desc'] = 'Lidio ödeme sistemini etkinleştir';

// Merchant application
$string['merchantapplication'] = 'Satıcı Başvurusu';
$string['merchantapplication_desc'] = 'Lidio satıcısı olmak için başvur';
$string['merchantapplicationdescription'] = 'Lidio satıcısı olmak için aşağıdaki formu doldurun. Onaylandıktan sonra, Lidio ödeme sistemi aracılığıyla ödeme almaya başlayabilirsiniz.';
$string['merchantstatus'] = 'Satıcı Durumu';
$string['merchantstatus_pending'] = 'Satıcı başvurunuz inceleme bekliyor.';
$string['merchantstatus_approved'] = 'Satıcı başvurunuz onaylandı.';
$string['merchantstatus_rejected'] = 'Satıcı başvurunuz reddedildi.';
$string['applyasmerchant'] = 'Satıcı Olarak Başvur';
$string['notamerchant'] = 'Satıcı olarak kayıtlı değilsiniz. Lütfen önce başvurun.';

// KYC Verification
$string['kycverification'] = 'KYC Doğrulama';
$string['kycverification_desc'] = 'Satıcı olmak için KYC doğrulamasını tamamlayın';
$string['kycverificationintro'] = 'Satıcı hesabınızı etkinleştirmek için kimliğinizi doğrulamamız gerekiyor. Lütfen aşağıdaki gerekli belgeleri yükleyin.';
$string['kycstatus'] = 'KYC Durumu';
$string['kycstatus_pending'] = 'KYC doğrulamanız beklemede.';
$string['kycstatus_approved'] = 'KYC doğrulamanız onaylandı.';
$string['kycstatus_rejected'] = 'KYC doğrulamanız reddedildi.';
$string['completekycverification'] = 'KYC Doğrulamasını Tamamla';
$string['kycwarning'] = 'Lütfen yüklenen tüm belgelerin net ve okunaklı olduğundan emin olun. Bulanık veya eksik belgeler reddedilecektir. Belgeler 5MB\'dan küçük olmalıdır.';
$string['kycuploaddocuments'] = 'Doğrulama Belgelerini Yükle';
$string['acceptedformats'] = 'Kabul Edilen Formatlar';
$string['maxfilesize'] = 'Maksimum Dosya Boyutu';
$string['uploaded'] = 'Yüklendi';
$string['upload'] = 'Yükle';
$string['delete'] = 'Sil';
$string['submit'] = 'Belgeleri Gönder';
$string['documentdeleted'] = 'Belge başarıyla silindi.';

// KYC Document Types
$string['passport'] = 'Pasaport';
$string['passport_desc'] = 'Pasaportunuzun net bir taramasını veya fotoğrafını yükleyin';
$string['id_card'] = 'Kimlik Kartı';
$string['id_card_desc'] = 'Kimlik kartınızın net bir taramasını veya fotoğrafını yükleyin (ön ve arka)';
$string['driving_license'] = 'Sürücü Belgesi';
$string['driving_license_desc'] = 'Sürücü belgenizin net bir taramasını veya fotoğrafını yükleyin';
$string['address_proof'] = 'Adres Kanıtı';
$string['address_proof_desc'] = 'Adresinizi gösteren bir fatura, banka ekstresi veya diğer resmi belgeyi yükleyin (son 3 ay içinde düzenlenmiş)';
$string['company_registration'] = 'Şirket Kayıt Belgesi';
$string['company_registration_desc'] = 'Şirket kayıt belgenizi veya işletme lisansınızı yükleyin (isteğe bağlı)';

// Additional KYC strings
$string['optional'] = 'İsteğe Bağlı';
$string['processing'] = 'İşleniyor...';
$string['documents_under_review'] = 'Belgeleriniz inceleniyor. Doğrulandıklarında size bilgi vereceğiz.';
$string['confirmdeletedocument'] = 'Bu belgeyi silmek istediğinizden emin misiniz?';
$string['filetoolarge'] = 'Dosya boyutu izin verilen maksimum boyutu (5MB) aşıyor.';
$string['invalidfiletype'] = 'Geçersiz dosya türü. Lütfen sadece JPG, JPEG, PNG veya PDF dosyaları yükleyin.';
$string['merchantstatusnotapproved'] = 'Satıcı durumunuz henüz onaylanmadı.';

// Navigation
$string['merchantdashboard'] = 'Satıcı Paneli';
$string['merchantsettings'] = 'Satıcı Ayarları';
$string['navigation'] = 'Navigasyon';
$string['dashboard'] = 'Panel';
$string['transactions'] = 'İşlemler';
$string['help'] = 'Yardım';

// Dashboard
$string['transactionhistory'] = 'İşlem Geçmişi';
$string['norecords'] = 'Kayıt bulunamadı.';
$string['viewdetails'] = 'Detayları Görüntüle';
$string['merchantaccountstatus'] = 'Hesap Durumu';
$string['totaltransactions'] = 'Toplam İşlemler';
$string['totalearnings'] = 'Toplam Kazanç';
$string['pendingpayments'] = 'Bekleyen Ödemeler';
$string['amount'] = 'Tutar';
$string['date'] = 'Tarih';
$string['id'] = 'ID';
$string['welcome'] = 'Hoş Geldiniz';
$string['overview'] = 'Genel Bakış';
$string['statistics'] = 'İstatistikler';
$string['activity'] = 'Son Aktivite';
$string['balance'] = 'Bakiye';
$string['withdraw'] = 'Para Çek';
$string['viewall'] = 'Tümünü Görüntüle';
$string['refresh'] = 'Yenile';

// Status
$string['pending'] = 'Beklemede';
$string['approved'] = 'Onaylandı';
$string['rejected'] = 'Reddedildi';

// Modern Dashboard
$string['earnings'] = 'Kazançlar';
$string['totalrevenue'] = 'Toplam Gelir';
$string['totalproducts'] = 'Toplam Ürünler';
$string['totalsales'] = 'Toplam Satışlar';
$string['totalcustomers'] = 'Toplam Müşteriler';
$string['salessummary'] = 'Satış Özeti';
$string['salesfunnel'] = 'Satış Hunisi';
$string['topproduct'] = 'En İyi Ürün';
$string['showall'] = 'Tümünü Göster';
$string['totalsold'] = 'Toplam satılan';
$string['viewsandclick'] = 'Görüntüleme ve Tıklama';
$string['averageviews'] = 'Ortalama görüntüleme';
$string['averageclick'] = 'Ortalama tıklama';
$string['agerange'] = 'Yaş Aralığı';
$string['visit'] = 'Ziyaret';
$string['click'] = 'Tıklama';
$string['purchased'] = 'Satın Alındı';
$string['newcustomerthisyear'] = 'Bu yıl yeni müşteri';
$string['customeracquisition'] = 'Müşteri kazanımınız yaklaşık olarak';
$string['eachmonth'] = 'artıyor her ay';
$string['youngadults'] = 'Genç yetişkinler en büyük alışveriş kitlenizi oluşturuyor,';
$string['ofcustomers'] = 'müşterilerin yaşı'; 
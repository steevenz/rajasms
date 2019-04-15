# RajaSMS
[RajaSMS][11] API PHP Class Library berfungsi untuk melakukan request API [RajaSMS][11]. Secara default fungsi SMS Masking dalam keadaan off, setiap pengiriman sms akan dilakukan tanpa masking, pastikan anda 
telah mengikuti regulasi yang berlaku untuk pengiriman sms masking terlebih dahulu.

Instalasi
---------
Cara terbaik untuk melakukan instalasi library ini adalah dengan menggunakan [Composer][7]
```
composer require steevenz/rajasms
```

Penggunaan
----------
```php
use Steevenz\Rajasms;

/*
 * --------------------------------------------------------------
 * Inisiasi Class RajaSMS
 *
 * @param string Username
 * @param string API Key
 * --------------------------------------------------------------
 */
 $rajasms = new Rajasms([
    'serverUrl' => '127.0.0.1' // API yang didapat ketika pendaftaran
    'apiKey => 'API_KEY_ANDA' // Ada pada panel RajaSMS
    'callbackUrl' => 'https://domain.anda/reporting-rajasms', // Callback ke controller reporting anda
    'sendingTime' => null, // Bersifat optional untuk membuat scheduling sms
 ]);

/*
 * --------------------------------------------------------------
 * Melakukan send sms
 *
 * @param string Phone Number
 * @param string Text
 * @param bool   Masking       Tidak menggunakan SMS Masking 
 *                             secara default
 *
 * @return object|bool
 * --------------------------------------------------------------
 */
 // send tanpa masking
 $status = $rajasms->send('082123456789','Testing Raja SMS API');

 // send dengan masking
 $status = $rajasms->send('082123456789','Testing Raja SMS API', TRUE);

// send sms ke banyak nomor sekaligus (max 1000)
$status = $rajasms->send(['081234567890', '08211234567890'], 'Testing Raja SMS API');

/*
 * --------------------------------------------------------------
 * Menerima Reporting pada Controller Callback
 *
 * @return object|bool
 * --------------------------------------------------------------
 */
 $rajasms->getReports();

/*
 * --------------------------------------------------------------
 * Melakukan checking credit balance
 *
 * @return mixed
 * --------------------------------------------------------------
 */
$credit = $rajasms->getCreditBalance();

```

Ide, Kritik dan Saran
---------------------
Jika anda memiliki ide, kritik ataupun saran, anda dapat mengirimkan email ke [steevenz@stevenz.com][3]. 
Anda juga dapat mengunjungi situs pribadi saya di [steevenz.com][1]

Bugs and Issues
---------------
Jika anda menemukan bugs atau issue, anda dapat mempostingnya di [Github Issues][6].

Requirements
------------
- PHP 7.2+
- [Composer][9]
- [O2System Curl][10]

[1]: http://steevenz.com
[2]: http://steevenz.com/blog/rajasms-api
[3]: mailto:steevenz@steevenz.com
[4]: http://github.com/steevenz/rajasms
[5]: http://github.com/steevenz/rajasms/wiki
[6]: http://github.com/steevenz/rajasms/issues
[7]: https://packagist.org/packages/steevenz/rajasms
[9]: https://getcomposer.org
[10]: http://github.com/o2system/curl
[11]: http://raja-sms.com

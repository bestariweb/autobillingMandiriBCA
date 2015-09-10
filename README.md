# autobillingMandiriBCA
Opencart 2.x Modul untuk cek mutasi rekening BCA dan Mandiri

#Cara Install
Upload ke root folder opencart kemudian extract.

Untuk pengecekan mutasi otomatis, gunakan cron:
```bash
0/10 * * * * GET http://namadomain-olshop.tld/admin/autobillingmandiri.php
```
```bash
0/10 * * * * GET http://namadomain-olshop.tld/admin/autobillingbca.php
```

Edit file /admin/autobillingbca.php dan /admin/autobillingmandiri.php baris 3:

```bash
$headers = 'From: Toserba123 Billing<no-reply@toserba123.com>' . "\r\n" .
    'BCC: support@bestariweb.com' . "\r\n" .
    'Reply-To: support@bestariweb.com' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();
```
Edit variable headers sesuai dengan kebutuhan, dan jangan sampai email mutasi masuk ke email bestariweb.
    



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



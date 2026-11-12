#!/bin/bash
# railway-init.sh
# Script ini dijalankan otomatis saat Railway pertama kali start
# untuk mengimpor database jika tabel belum ada

echo "=== Railway Database Init ==="

# Tunggu MySQL Railway siap
until mysql -h "$MYSQLHOST" -P "$MYSQLPORT" -u "$MYSQLUSER" -p"$MYSQLPASSWORD" "$MYSQLDATABASE" -e "SELECT 1" > /dev/null 2>&1; do
  echo "Menunggu MySQL Railway..."
  sleep 2
done

echo "MySQL siap! Mengecek tabel..."

TABLE_COUNT=$(mysql -h "$MYSQLHOST" -P "$MYSQLPORT" -u "$MYSQLUSER" -p"$MYSQLPASSWORD" "$MYSQLDATABASE" -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$MYSQLDATABASE';" -sN 2>/dev/null)

if [ "$TABLE_COUNT" -lt 5 ]; then
  echo "Mengimpor database..."
  mysql -h "$MYSQLHOST" -P "$MYSQLPORT" -u "$MYSQLUSER" -p"$MYSQLPASSWORD" "$MYSQLDATABASE" < /var/www/html/database.sql
  echo "Database berhasil diimpor!"
else
  echo "Database sudah ada ($TABLE_COUNT tabel), skip import."
fi

echo "Memulai Apache..."
apache2-foreground

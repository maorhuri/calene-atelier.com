---
description: חקירת עומס בשרת Linux - איתור בעיות ביצועים
---

# Server Load Investigation Workflow

## שלב 1: סקירה כללית
```bash
# התחברות לשרת
ssh -i ~/.ssh/server_103 root@SERVER_IP

# בדיקת Load ו-Uptime
uptime

# מידע על CPU
nproc  # מספר cores

# מידע על זיכרון
free -h

# בדיקת SWAP - אם מלא זו בעיה!
# Swap used > 50% = בעיה
```

## שלב 2: זיהוי תהליכים כבדים
```bash
# Top 20 תהליכים לפי CPU
ps aux --sort=-%cpu | head -21

# Top 20 תהליכים לפי RAM
ps aux --sort=-%mem | head -21

# ספירת php-fpm לפי user
ps aux | grep php-fpm | grep -v grep | awk '{print $1}' | sort | uniq -c | sort -rn | head -10
```

## שלב 3: בדיקת MySQL/MariaDB
```bash
# חיבורים פעילים
mysql -e 'SHOW STATUS LIKE "Threads_connected";'

# Slow queries count
mysql -e 'SHOW GLOBAL STATUS LIKE "Slow_queries";'

# חיבורים לפי user
mysql -e 'SELECT user, COUNT(*) as conn FROM information_schema.processlist GROUP BY user ORDER BY conn DESC;'

# Queries פעילות (לא Sleep)
mysql -e 'SHOW FULL PROCESSLIST' | grep -v Sleep | head -20

# Queries ארוכות (מעל 5 שניות)
mysql -e 'SELECT id, user, db, time, state, LEFT(info, 100) FROM information_schema.processlist WHERE command != "Sleep" AND time > 5 ORDER BY time DESC;'
```

## שלב 4: זיהוי Queries בעייתיות
```bash
# בדיקת slow query log
tail -100 /var/log/mysql/slow.log | grep -A2 'Query_time' | head -30

# חיפוש TranslatePress queries (בעיה נפוצה!)
mysql -e 'SHOW FULL PROCESSLIST' | grep 'trp_gettext'

# חיפוש backup/export queries
mysql -e 'SHOW FULL PROCESSLIST' | grep 'SQL_NO_CACHE'

# גודל טבלאות postmeta (לעתים קרובות בעייתיות)
mysql -e "SELECT table_schema, table_name, table_rows, ROUND(data_length/1024/1024,2) as Data_MB FROM information_schema.tables WHERE table_name LIKE '%postmeta%' ORDER BY table_rows DESC LIMIT 10;"
```

## שלב 5: בדיקת Disk I/O
```bash
# I/O statistics
iostat -x 1 3 | tail -15

# אם %util > 80% = צוואר בקבוק ב-disk
```

## שלב 6: בדיקת SWAP users
```bash
# מי משתמש ב-SWAP
for pid in $(ls /proc | grep -E '^[0-9]+$' | head -100); do 
  swap=$(awk '/VmSwap/{print $2}' /proc/$pid/status 2>/dev/null)
  if [ "$swap" -gt 1000 ] 2>/dev/null; then
    echo "$swap KB - $(cat /proc/$pid/comm 2>/dev/null) (PID: $pid)"
  fi
done | sort -rn | head -10
```

---

# פתרונות נפוצים

## בעיה: TranslatePress גורם לעומס
```bash
# זיהוי
mysql -e 'SHOW FULL PROCESSLIST' | grep -c 'trp_gettext'

# פתרון 1: הוסף אינדקס
mysql DATABASE_NAME -e 'ALTER TABLE wp_trp_gettext_he_il ADD INDEX idx_original_id (original_id);'

# פתרון 2: כבה את הפלאגין זמנית
mv /path/to/wp-content/plugins/translatepress-multilingual /path/to/wp-content/plugins/translatepress-multilingual.disabled
```

## בעיה: SWAP מלא
```bash
# ניקוי SWAP (זהירות - יגרום לעומס זמני!)
sync && echo 3 > /proc/sys/vm/drop_caches
swapoff -a && swapon -a
```

## בעיה: הרג queries תקועות
```bash
# הרג queries של user מסוים שרצות יותר מ-30 שניות
mysql -e "SELECT id FROM information_schema.processlist WHERE user='USERNAME' AND time > 30 AND command != 'Sleep';" | tail -n +2 | while read id; do mysql -e "KILL $id;"; done
```

## בעיה: הרג php-fpm של user מסוים
```bash
pkill -u USERNAME -f php-fpm
```

## בעיה: טבלאות postmeta ענקיות
```bash
# הוסף אינדקס
mysql DATABASE_NAME -e 'ALTER TABLE wp_postmeta ADD INDEX idx_meta_key (meta_key(50));'

# נקה transients ישנים
mysql DATABASE_NAME -e "DELETE FROM wp_options WHERE option_name LIKE '%_transient_%' AND option_name LIKE '%timeout%';"
mysql DATABASE_NAME -e "DELETE FROM wp_postmeta WHERE meta_key LIKE '%_transient_%';"
```

---

# Red Flags - סימני אזהרה

| מדד | תקין | אזהרה | קריטי |
|-----|------|-------|-------|
| Load Average | < cores | 1-2x cores | > 2x cores |
| SWAP Used | < 20% | 20-50% | > 50% |
| MySQL CPU | < 50% | 50-80% | > 80% |
| Slow Queries | < 100 | 100-1000 | > 1000 |
| Threads Connected | < 50 | 50-100 | > 100 |

---

# Quick Health Check (one-liner)
```bash
ssh root@SERVER_IP "uptime && free -h | grep -E '(Mem|Swap)' && ps aux --sort=-%cpu | head -5 && mysql -e 'SHOW STATUS LIKE \"Threads_connected\";'"
```

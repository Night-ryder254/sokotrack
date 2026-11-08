# MySQL for SokoTrack, run as a Render private service with a disk mounted at /var/lib/mysql.
# 8.4 is the current long-term-support release (8.0 reached end of life in April 2026).
FROM mysql:8.4

# Sized for a 512 MB instance: Performance Schema off and a modest InnoDB buffer pool.
CMD ["mysqld", \
     "--character-set-server=utf8mb4", \
     "--collation-server=utf8mb4_unicode_ci", \
     "--innodb-buffer-pool-size=128M", \
     "--performance-schema=OFF", \
     "--max-connections=60"]

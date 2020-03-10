#!/bin/bash
# первый параметр -d (день недели)  -w(номер недели в месяце) -m (месяц в году) влияет на навзание файла.
# первый параметр или второй называвние БД. пароли храняться в .cnf
echo '~~~~~~~~~~~'
# $( cd "$(dirname "$0")" ; pwd -P );
SCRIPT=$(realpath $0)
PATH_SCRIPT=$(dirname $SCRIPT)
#DB_NAME='aleksey_olgatravel_1219'
# database  = "olgatravel"
date_mask=D%u
DB_NAME='wordpress'

set_date_mask() {
  case "$1" in
  -d) date_mask=$(date +D%u) ;;
  -w)
    day_in_month=$(date +%d)
    # shellcheck disable=SC2034
    let week_in_month=$day_in_month/7
    date_mask="W$week_in_month"
    ;;
  -m) date_mask=$(date +M%m) ;;
  *)
    DB_NAME=$1
    ;;
  esac
}

if [ -n "$2" ]; then
  set_date_mask $1
  DB_NAME=$2
elif [ -n "$1" ]; then
  set_date_mask $1
fi

echo 'путь к скрипту:'$PATH_SCRIPT
Dump_file_name=$PATH_SCRIPT/"$DB_NAME"_"$date_mask".sql.gz
save_mask=$(umask)
umask 0166
mysqldump --defaults-extra-file=$PATH_SCRIPT/run_bk.cnf $DB_NAME | gzip >$Dump_file_name

Size_dump=$(stat -c %s $Dump_file_name)

if [ $Size_dump -lt 512 ]; then
  echo "Warning.Рамер файла  $Dump_file_name меньше 512" >&2
fi
echo 'Рамер файла:'$Size_dump

umask $save_mask

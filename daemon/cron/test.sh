#!/bin/sh

name=$2   #标记名称
dt=$3     #日期
hour=$4   #小时
log=$1/$2.$3$4.$name.log    #错误日志路径
val=''    #执行结果返回值

curl=`which curl`
rsync=`which rsync`
i=0

#-----------循环10次，返回Success则退出
while [[ $val != "Success" ]]
do 
    ((i++))
    if [[ $i -gt 10 ]];
    then
        $rsync $log r2.data.sina.com.cn::rsync_tag_error/
        exit 255   
    fi
    echo "$curl -sS 'http://api.pso.data.sina.com.cn/monitor/index.php/interface/tagServiceOuter?tag_name='$name'&date='$dt'&hour='$hour'&act=M'" > $log 
    $curl -sS 'http://api.pso.data.sina.com.cn/monitor/index.php/interface/tagServiceOuter?tag_name='$name'&date='$dt'&hour='$hour'&act=M' >> $log
    val=`$curl -sS 'http://api.pso.data.sina.com.cn/monitor/index.php/interface/tagServiceOuter?tag_name='$name'&date='$dt'&hour='$hour'&act=Q'`
    
    if [[ $val != "Success" ]];
    then
        sleep 60
    fi
done

exit 0


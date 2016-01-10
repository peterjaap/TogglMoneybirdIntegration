#!/bin/bash
ENTRIES=$(whiptail --title "Time entries" --checklist \
"Choose time entries to invoice" 25 110 16 \

3>&1 1>&2 2>&3)
 
exitstatus=$?
if [ $exitstatus = 0 ]; then
    echo $ENTRIES
else
    echo ""
fi
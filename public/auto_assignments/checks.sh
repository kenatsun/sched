echo "-----------";
echo 'angie' clean after self: 'yes'
grep 'angie.*angie' schedule.txt
echo "-----------";
echo 'bruno' avoids 'fatima'
grep 'bruno' schedule.txt | grep 'fatima'
echo "-----------";
echo 'fatima' avoids 'bruno'
grep 'fatima' schedule.txt | grep 'bruno'
echo "-----------";
echo 'fatima' clean after self: 'no'
grep 'fatima.*fatima' schedule.txt
echo "-----------";
echo 'keithg' avoids 'catherine'
grep 'keithg' schedule.txt | grep 'catherine'
echo "-----------";
echo 'keithg' clean after self: 'yes'
grep 'keithg.*keithg' schedule.txt
echo "-----------";
echo 'lindal' clean after self: 'yes'
grep 'lindal.*lindal' schedule.txt
echo "-----------";
echo 'marys' clean after self: 'no'
grep 'marys.*marys' schedule.txt
echo "-----------";
echo 'meg' clean after self: 'no'
grep 'meg.*meg' schedule.txt
echo "-----------";
echo 'rod' avoids 'hope'
grep 'rod' schedule.txt | grep 'hope'
echo "-----------";
echo 'rod' avoids 'kelly'
grep 'rod' schedule.txt | grep 'kelly'
echo "-----------";
echo 'rod' prefers 'pam'
grep 'rod' schedule.txt | grep 'pam'
echo "-----------";
echo 'rod' clean after self: 'yes'
grep 'rod.*rod' schedule.txt
echo "-----------";
echo 'yimiau' avoids 'thomas'
grep 'yimiau' schedule.txt | grep 'thomas'
echo "-----------";
echo 'yimiau' clean after self: 'no'
grep 'yimiau.*yimiau' schedule.txt

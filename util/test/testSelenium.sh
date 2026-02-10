
for i in `seq 1 20`;
do
  curl -X POST http://localhost:4444/wd/hub/session -d '{"desiredCapabilities":{"browserName":"chrome"}}' &
done

FAIL=0
for job in `jobs -p`
do
    wait $job || let "FAIL+=1"
done

if [ "$FAIL" == "0" ];
then
	echo "wait complete"
else
	echo "failures: $FAIL"
	exit 7
fi
echo
echo done
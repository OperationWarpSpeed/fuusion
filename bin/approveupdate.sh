echo "Do you wish to publish this update?"
select yn in "Approve" "No"; do
    case $yn in
        Approve ) exit 0; break;;
        No ) exit 1;;
    esac
done
exit 1


# Change every instance of pmpro-membership-maps below to match your actual plugin slug
#---------------------------
# This script generates a new pmpro.pot file for use in translations.
# To generate a new pmpro-membership-maps.pot, cd to the main /pmpro-membership-maps/ directory,
# then execute `languages/gettext.sh` from the command line.
# then fix the header info (helps to have the old pmpro.pot open before running script above)
# then execute `cp languages/pmpro-membership-maps.pot languages/pmpro-membership-maps.po` to copy the .pot to .po
# then execute `msgfmt languages/pmpro-membership-maps.po --output-file languages/pmpro-membership-maps.mo` to generate the .mo
#---------------------------
echo "Updating pmpro-membership-maps.pot... "
xgettext -j -o languages/pmpro-membership-maps.pot \
--default-domain=pmpro-membership-maps \
--language=PHP \
--keyword=_ \
--keyword=__ \
--keyword=_e \
--keyword=_ex \
--keyword=_n \
--keyword=_x \
--sort-by-file \
--package-version=1.0 \
--msgid-bugs-address="info@paidmembershipspro.com" \
$(find . -name "*.php")
echo "Done!"
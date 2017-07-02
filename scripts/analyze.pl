#!/usr/bin/perl
#
open (COUNT, "/usr/bin/mysql -e \"select count(*) from qruqsp_tnc_kisspackets\" qruqsp |") || die "Unable to open mysql to count packets\n";
while (<COUNT>) {
    print $_;
    if ($_ =~ /^(\d+)$/) {
        $count = $1;
        print "count=$count packets\n";
    }
}
close (COUNT);

print "-=-=-=-=-=-=-=-=-\n\n";
open (DECODE, "/qruqsp/sites/qruqsp.local/site/qruqsp-mods/tnc/scripts/decode_more.php |") || die "Unable to execute /qruqsp/sites/qruqsp.local/site/qruqsp-mods/tnc/scripts/decode_more.php\n";
while (<DECODE>) {
    chomp;
    # 1c  28 00011100  [^\]
    # 2d  45 00101101  [-]
    #  d  13 00001101  []
    #               c0    192 11000000  [�]
    if ($_ =~ /^\s*\S++\s+\d+\s+\d+\s+\[(.*)\]\s*$/) {
        $text = $text . $1;
        # print "DEBUG text=$text | $_\n";
    }
    #Array
    # elsif ($_ =~ /^Array$/) {
        # print "DEBUG text=$text | $_\n";
    # }

    #(
    #    [port] => 0
    elsif ($_ =~ /^\s+\[(\w+)\]\s+=>\s+(\S+)\s*$/) {
        $key = $1;
        $value = $2;
        next if ($key =~ /^\d+$/);
        $aprs{$key} = $value;
        $summary{$key}{$value}++;
    }
    #    [command] => 0
    #    [dest_callsign] => S2TY3X
    #    [dest_ssid] => `
    #    [src_callsign] => W5HRC
    #    [src_ssid] => p
    #    [digipeaters] => Array
    #        (
    #            [0] => Array
    #                (
    #                    [callsign] => WIDE1
    #                    [ssid] => 1
    #                )
    #
    #            [1] => Array
    #                (
    #                    [callsign] => WIDE2
    #                    [ssid] => 1
    #                )
    #
    #        )
    #
    #    [data] => Array
    #        (
    #            [0] => 96
    #            [1] => 125
    #            [2] => 39
    #            [3] => 64
    #            [4] => 108
    #            [5] => 32
    #            [6] => 28
    #            [7] => 45
    #            [8] => 47
    #            [9] => 96
    #            [10] => 52
    #            [11] => 52
    #            [12] => 52
    #            [13] => 46
    #            [14] => 49
    #            [15] => 50
    #            [16] => 53
    #            [17] => 77
    #            [18] => 72
    #            [19] => 122
    #            [20] => 95
    #            [21] => 37
    #            [22] => 13
    #        )
    #
    #    [control] => 3
    #    [protocol] => 240
    #)
    elsif ($_ =~ /^\)$/) {
        $aprs{"text"} = $text;
        #                   3230.18N/09751.03W
        # if ($text =~ /(\d\d\d\d\.\d\d)([NS])\/(\d\d\d\d\.\d\d)([EW])/) {
        if ($text =~ /(\d\d\d\d\.\d\d)([NS])/) {
            $latdec=$1;
            $latdir=$2;
            if ($text =~/(\d\d\d\d\.\d\d)([EW])/) {
                $londec=$1;
                $londir=$2;
                # print "DEBUG: latdec=$latdec latdir=$latdir / londec=$londec londir=$londir | $text\n";
                $src_callsign = $aprs{"src_callsign"};
                $coord{$latdir}{$latdec}{$londir}{$londec}{$src_callsign} ++;
            }
        }
        # elsif ($text =~ /(\d+\.\d+)\s*MHz/i) {
        elsif ($text =~ /(\d+\.\d+)/) {
            $frequency = $1;
            $src_callsign = $aprs{"src_callsign"};
            $freq{$frequency}{$src_callsign} ++;
            # print "DEBUG: frequency=$frequency src_callsign=$src_callsign\n";
        }
        foreach $key (sort keys %aprs) {
            print "$key=$aprs{$key}\n";
        }
        print "=======================================\n";
        $text = '';
        %aprs = null;
    }
}
close (DECODE);

print "\n\n=-=-=-=-=-=-=-=-=-=-= Frequencies Heard =-=-=-=-=-=-=-=-=-=-=\n";
foreach $frequency (sort keys %freq) {
    print "$frequency :";
    foreach $src_callsign (sort keys %{ $freq{$frequency} } ) {
        print " $src_callsign";
    }
    print "\n";
}

print "\n\n=-=-=-=-=-=-=-=-= Beacons with Coordinates =-=-=-=-=-=-=-=-=\n";
foreach $latdir (sort keys %coord) {
    foreach $latdec (sort keys %{ $coord{$latdir} }) {
        foreach $londir (sort keys %{ $coord{$latdir}{$latdec} }) {
            foreach $londec (sort keys %{ $coord{$latdir}{$latdec}{$londir} }) {
                foreach $src_callsign (sort keys %{ $coord{$latdir}{$latdec}{$londir}{$londec} }) {
                    print "DEBUG: $latdec$latdir \/ $londec$londir $src_callsign $coord{$latdir}{$latdec}{$londir}{$londec}{$src_callsign}\n";
                }
            }
        }
    }
}


print "\n\n=-=-=-=-=-=-=-= Summary of Values for Each Key =-=-=-=-=-=-=-=\n";
foreach $key (sort keys %summary) {
    print "$key:";
    foreach $value (sort keys %{ $summary{$key} } ) {
        print " $value=$summary{$key}{$value}";
    }
    print "\n\n";
}

print "$count total beacons found in database\n";


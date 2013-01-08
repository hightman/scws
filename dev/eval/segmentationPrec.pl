#!/usr/local/bin/perl5 
#
# This program is used to calculate the edit distance bwteen two segmented text
# 
# By Joy, joy@cs.cmu.edu
# Jan 2004
#
# Usage:
#	perl5 segmentationPrec.perl ref hyp
#

#check parameters
if($#ARGV<1){
	print STDERR "\n-----------------------------------------------";
	print STDERR "\nUsage:";
	print STDERR "\n\tperl5 perl5 segmentationPrec.perl ref hyp";
	print STDERR "\n-----------------------------------------------\n";
	
	exit;
}

open RefFile, $ARGV[0];
open HypFile, $ARGV[1];

$totalIns=0;
$totalDel=0;
$totalSub=0;

$totalSepMarkerInRef=0;
$totalSepMarkerInHyp=0;

$sentId=1;
while(<RefFile>){

	$thisIns=0;
	$thisDel=0;
	$thisSub=0;
	
	$line1=$_;
	$line2=<HypFile>;
	
	#normalize two lines
	$line1=~s/\x0A//;
	$line1=~s/\x0D//;
	$line1=~s/\x20+/\x20/g;
	$line1=~s/\t+/\x20/g;
	$line1=~s/^\x20+//g;
	$line1=~s/\x20+\Z//g;
	
	$line2=~s/\x0A//;
	$line2=~s/\x0D//;	
	$line2=~s/\x20+/\x20/g;
	$line2=~s/\t+/\x20/g;
	$line2=~s/^\x20+//g;
	$line2=~s/\x20+\Z//g;
	
	$index1=0;
	$index2=0;
	
	$len1=length($line1);
	$len2=length($line2);
	
	$totalSepMarkerInRef+=($line1=~s/\x20/\x20/g);
	$totalSepMarkerInHyp+=($line2=~s/\x20/\x20/g);
	
	while(($index1<$len1)&&($index2<$len2)){
		$char1=substr($line1,$index1,1);
		$char2=substr($line2,$index2,1);
		
		if(($char1 ne " ")&&($char2 ne " ")){
			if($char1 ne $char2){			
				print STDERR "At Line1=$line1 Line2=$line2 Can not match char1 +$char1+ +$char2+! \n";
				exit;
			}
			else{
				$index1++;
				$index2++;
			}
		}
		else{
	
			if($char1 eq " "){
				if($char2 eq " "){
					$thisSub++;			
					$index1++;
					$index2++;
					
				}
				else{				
					$thisIns++;
					$index1++;
				}				
			}
			else{					
				$thisDel++;
				$index2++;
			}
		}
	}
	
	$sentId++;
	
	$totalSub+=$thisSub;
	$totalIns+=$thisIns;
	$totalDel+=$thisDel;
	
}
print "Total:\n\tSub=$totalSub\n\tIns=$totalIns\n\tDel=$totalDel\n\n";
$prec=$totalSub/$totalSepMarkerInHyp;
printf "\tPrecision: %.2f\%\n", 100*$prec;
$recall=$totalSub/$totalSepMarkerInRef;
printf "\tRecall: %.2f\%\n", 100*$recall;
printf "\tF-1: %.2f\n", 2*$prec*$recall/($prec+$recall);

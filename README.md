## **VCF to FHIR Converter**


### Introduction

VCF-2-FHIR conversion is a component of a broader project, a [FHIR](https://www.hl7.org/fhir/)-enabled genomic data server (or Genomic Archiving and Communication System, GACS), tentatively slated to be released onto github in late 2019. A high level model for the FHIR-enabled GACS is shown in the following figure.

  

![](https://lh5.googleusercontent.com/lfwQpgBaqKfuAotrYwKdCd7YNcMo9S2HRAgnNSnHmXfRnv5jFDhwHZi3vGFYSfQEnA4ttVJXFOHlx_9cE2cwchOSguVugASdiTgbKpkz_dE--R-Wl4gKIZ8_ZpLB4leUpMJRPd0x)

In this design, GACS stores raw genomics files ([FASTQ](https://github.com/samtools/hts-specs/), [SAM](https://github.com/samtools/hts-specs/), [VCF](https://github.com/samtools/hts-specs/), [BED](https://genome.ucsc.edu/FAQ/FAQformat.html#format1), etc) generated from next-generation sequencing. Our draft GACS FHIR API has a gene as the query parameter, and returns variants, variant zygosity (e.g.heterozygous, homozygous), and phase relationships (e.g. whether two heterozygous variants are on the same or homologous chromosomes) found within that gene; or an indication that the gene hasn’t been studied. Results are formatted as a FHIR Diagnostic Report conforming to the January 2019 balloted [FHIR Genomics guide](http://www.hl7.org/fhir/uv/genomics-reporting/2019Jan/index.html). Ultimately, we plan to submit the GACS FHIR API specification to HL7 for community review and balloting.

  

Implementation of said API is via an ‘On-demand FHIR Translator’. In this model, GACS first determines if any data is on file for the requested gene by searching an index automatically generated from SAM files. Where the gene has been sequenced, GACS performs real-time extraction of variants from VCF and feeds them to a VCF-to-FHIR converter, which performs real-time conversion into corresponding FHIR objects within the FHIR Diagnostic Report to be returned, as illustrated in the following figure.

![](https://lh6.googleusercontent.com/2oyJZ681JihIv0mm90OafEGQLUkx72h5KTuRof7dWfM5eEnzefs5Y3JxA2chnufVrGdWMqQE7fxRS4OMTsRevU7DbAvErSH38Qpz7o4M_UG3c9-7PyWlL7HlGK3kgI0LkU9InLpI)

We provide the VCF-to-FHIR converter here. Software is available for use under an [Apache 2.0 license](https://opensource.org/licenses/Apache-2.0), and is intended solely for experimental use, to help further Genomics-EHR integration exploration. Software is expressly not ready to be used with identifiable patient data or in delivering care to patients. Code issues should be tracked here. Comments and questions can also be directed to [info@elimu.io](mailto:info@elimu.io).

### VCF-to-FHIR Conversion
A detailed description of the conversion algorithm is below. We currently translate simple variants, along with zygosity and phase relationships. We are working on enhancing the conversion to accommodate structural variants, but do not yet have a slated date for when that code will be available. We also anticipate a code update later this year once January 2019 ballot comments against the FHIR Genomics guide have been resolved and applied.

We tested the algorithm against 17 anonymized VCF files obtained from the [1000 Genomes project](https://www.nature.com/articles/nature15393). From each file, we simulated real-time extraction of gene-specific variants using bcftools, creating 3 gene-specific (TPMT, CYP2C19, CYP2D6) VCF files for each of 17 patients, for a total of 51 VCF files. Each of these files was then translated, via the VCF-to-FHIR converter, into a corresponding FHIR Genomics report. Source VCF files, along with corresponding FHIR Genomics reports (in XML and JSON) can be found in the 'public' folder on this site. 

#### Extract parameters from filename

In the full incarnation of the on-demand translator, the VCF-to-FHIR converter will obtain patientID and build from the server, and gene from the query. For the stand-alone VCF-to-FHIR converter, patient ID, build, and gene need to be supplied in the file name. Patient ID can be any string without whitespace. Build must be one of ‘b36’ (aka NCBI Build 36, hg18), ‘b37’ (aka GRCh37, hg19), or ‘b38’ (aka GRCh38, hg38). Gene must be a valid [HGNC gene symbol](https://www.genenames.org/). Filename must be formatted as: [patientId].[b36|b37|b38].[HGNC gene symbol].(anything).vcf (e.g. ‘NA120003.b37.CYP2D6.vcf’).

#### Create FHIR diagnostic report

Create a FHIR Diagnostic Report containing exactly 1 FHIR region studied profile, 0..* FHIR described variant profiles, and 0..* FHIR sequence phase relationship profiles. The region studied profile reflects back the gene queried for, with an indication of whether or not any variants were found. There is one described variant profile for each non-filtered row in the VCF. Where the VCF includes phasing information, each pairwise relationship is reflected in a FHIR sequence phase relationship profile.

-   Diagnostic Report
    - Contained
       - 1..1 region studied
       - 0..* described variant
       - 0..* sequence phase relationship  


#### Create 1..1 FHIR region studied profile

-   Observation.valueCodeableConcept
    - If VCF file contains variants (after exclusion): Present (LA9633-4)
    - If VCF file does not contain variants: Absent (LA9634-2)
    

-   Component gene studied – reflect back the gene in the query request
    - Component.valueCodeableConcept
       - TPMT: code=’HGNC:12014’; display=’TPMT’
       - CYP2C19: code=’HGNC:2621’; display=’CYP2C19’
       - CYP2D6: code=’HGNC:2625’; display=’CYP2D6’
    

#### Create 0..* FHIR described variant profiles

Go row by row through the VCF, converting each VCF row into an instance of described variant.

-   These are the VCF fields we use: CHROM, POS, REF, ALT, FILTER, INFO.SVTYPE, FORMAT.GT, FORMAT.PS
    
-   Exclude these VCF records:
    - Header rows (beginning with ‘#’)
    - REF is not a simple character string
    -  ALT is not a simple character string
    - FILTER <> ‘PASS’ or ‘.’
    -  INFO.SVTYPE is present

-   Create described variant profile
       - Component genome build: populate with LOINC answer for build (e.g. LA14029-5 for GRCh37)
       - Component reference sequence: populate with reference sequence corresponding to build (e.g. NC_000010.10 for GRCh37)
       - Component allelic state:
          -  If FORMAT.GT is x/x (e.g. 0/0, 1/1): LA6705-3 : Homozygous
          -  If FORMAT.GT is x/y (e.g. 0/1, 1/0, 1|0): LA6706-1 : Heterozygous
     -  Component ref allele: Populate with REF
     - Component alt allele: Populate with ALT
     -  Component coordinate system: = ‘1’
     - Component allele start/end: Populate valueRange/low with POS

#### Create 0..* FHIR sequence-phase-relationship profiles

-   Identify rows where FORMAT.PS is numeric, where FORMAT.GT contains “|”, and where FORMAT.GT is x|y (e.g. 0|1 or 1|0)
     - Order rows by FORMAT.PS, by POS
     - Where > 1 row have same FORMAT.PS
        - Beginning with the 2nd row, create a sequence-phase relationship, linking it to the prior row. If x|y and y|x then ‘Trans’ (code=’TBD-cisTrans’). If x|y and x|y then ‘Cis’ (code=’TBD-cisTrans’).
        - Examples
          - Heterozygous variants are TRANS:  
    6 18142205 . C T . . . GT:PS 1|0:18142205  
    6 18142422 . A C . . . GT:PS 0|1:18142205
           - Heterozygous variants are CIS:  
6 18142289 . A G . . . GT:PS 1|0:18142289  
6 18142308 . A G . . . GT:PS 1|0:18142289


## Steps to run the converter on you local machine



**Step 1**- Setting up your system
- Download and install XAMPP from the following link: https://www.apachefriends.org/download.html
- Download and install composer from: https://laravel.com/docs/5.4/#server-requirements
-  Download and install java from: https://www.java.com
- Download zip file of the project from the download button of the git repository
- Extract zip in your local machine
- Open the extracted folder (you should see all the files ex: app, bootstrap, config, etc.)
- Open Command Prompt/Terminal in that folder
- Run the following command: 
    - composer install 

This will install the required components for the converter to run.

Now you are all set to convert .vcf files into fhir.json and fhir.xml format.
<br/>  
**Step 2**- Converting .vcf files
- For Linux/Mac: Open Terminal, For Windows open Command Prompt
- Navigate to the 'vcf-2-fhir' folder or open Terminal/Command Prompt in the 'vcf-2-fhir' folder
- Run the following command 
    - for Windows: php artisan serve
    - for Linux: sudo php artisan serve
- Open a web browser and visit "http://localhost:8000", you should see a VCF-TO-FHIR landing page
- Click on "Choose file" button
- Navigate to your desired '.vcf' file in the newly opened file explorer window
- Select the file and click 'open'
- You should be able to see your selected file name beside 'Choose file' button
- Click on "Click To Convert" button
- You will see "fhir.xml has been successfully created" message if your file has been converted successfully
- Click on "VIEW XML FILE" to see fhir.xml file
- If you wish to see fhir.json format:
	-  Open a new Terminal/Command Prompt window
	-  Navigate to the folder "vcf-to-fhir/public" using Terminal/Command Prompt or you can navigate to the folder and then open Terminal/Command Prompt in that folder. Here you should see "xmltojson.jar" file
	- Run the following command "java -jar xmltojson.jar `<local file path where fhir.xml is stored> <filepath where you want to store fhir.json file>`"
	- ex: "java -jar xmltojson.jar Downloads\vcf-to-fhir\public\fhir.xml \Documents\converted"




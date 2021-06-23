<?php
//
//  quick_help.php - Keeping data for SoftNAS quick help here
//
//  Copyright (c) 2012 SoftNAS, Inc.  All Rights Reserved.
//
//
$quick_help_arr = array();

class QuickHelp {
	
/*
"element_id" => array(
	"id" => 000,
	"text" => "",
	"doc_hl" => array(),
	"kb_hl" => array(),
	"video_hl" => array(),
	"related_hl" => array()
)
*/

	public $help_storagecenter = array(
		"add_platinum_license" => array(
			"id" => 14467,
			"text" => "This SoftNAS feature requires a Platinum License be active on this instance.
						
						To obtain the license key, you will need to contact SoftNAS via <a href='mailto:sales@softnas.com' target='_top'>sales@softnas.com</a> or register on our website via out <a href='https://www.softnas.com/wp/contact-us/' target='_blank'>Contact Us</a> page.
						
						If you have received a Platinum license key, open the email and apply it here.",
			"doc_hl" => array("Activating Your SoftNAS License" => "https://docs.softnas.com/display/SD/Activating+Your+SoftNAS+License"),
			"kb_hl" => array(),
			"video_hl" => array(),
			"related_hl" => array()
		)
	);
	public $help_administrator = array();
	public $help_cifs = array();
	public $help_cloud_essentials = array(
	
		"storage_configuration" => array(
			"id" => 14060,
			"text" => "Cloud Essentials uses Object Storage Devices. Object storage is ideal for unstructured data like music, images, and video files. It is also a good selection for storage of backup files, database dumps and log files as well as large data sets that are not accessed frequently and do not change frequently. Select the appropriate object storage for the volume being created.",
			"doc_hl" => array(),
			"kb_hl" => array(),
			"video_hl" => array(
				"How To: Understanding and Adding Blob Storage for SoftNAS Cloud on Azure" => "https://www.youtube.com/embed/gQ8J9Fyfm-U"
			),
			"related_hl" => array(
				"Best Practices - S3 Cloud Disk" => "https://docs.softnas.com/display/SD/S3+Cloud+Disk+Best+Practices",
				"SoftNAS Cloud S3 Disk Overview" => "https://docs.softnas.com/pages/viewpage.action?pageId=65703",
				"Amazon S3 Storage" => "https://aws.amazon.com/s3/storage-classes/"
			)
		),
		"volume_configuration" => array(
			"id" => 14061,
			"text" => "Enter the name of the volume to be created. This will become part of the mountpoint used to share it though the protocol selected.
						
						Enter the size of the volume to be created.
						
						Note: The actual size of the object devices created for the volume will be slightly larger to offset ZFS overhead. If the volume being created is under 100GBs, the resulting size of the volume will be slightly larger however, if the volume is equal to or larger than 100GBs, the resulting volume will be slightly smaller.
						
						When choosing a Maximum Disk Size, please keep in mind that a SoftNAS Cloud® license will be required for the maximum amount of storage planned for use. VMs will consume approximately 30GB of disk space.
						
						Select the protocol to be used with the volume being created. NFS and CIFS can share a common volume. Selecting iSCSI will not allow sharing the volume with NFS or CIFS.",
			"doc_hl" => array(
				"SoftNAS Cloud® Essentials" => "https://docs.softnas.com/pages/viewpage.action?pageId=8454835"
			),
			"kb_hl" => array(),
			"video_hl" => array(),
			"related_hl" => array()
		),
		"active_directory" => array(
			"id" => 14062,
			"text" => "Enter the Active Directory Domain Name this volume will be accessed from.
						
						Enter the Administrator ID and password for the Active Directory to be used.",
			"doc_hl" => array(
				"SoftNAS Cloud® Essentials" => "https://docs.softnas.com/pages/viewpage.action?pageId=8454835"
			),
			"kb_hl" => array(),
			"video_hl" => array(),
			"related_hl" => array(
				"Active Directory Configuration" => "https://docs.softnas.com/display/SD/Active+Directory+Configuration",
				"Configuring Kerberos to Connect to Active Directory" => "https://docs.softnas.com/display/SD/Configuring+Kerberos+to+Connect+to+Active+Directory",
				"Adding Domain Controllers as DNS Server for SoftNAS" => "https://docs.softnas.com/display/SD/Adding+Domain+Controllers+as+DNS+Server+for+SoftNAS",
				"How to set up Minimum Permissions required to join SoftNAS to AD" => "https://docs.softnas.com/display/SD/How+to+set+up+Minimum+Permissions+required+to+join+SoftNAS+to+AD",
				"MTU 9000" => "https://docs.softnas.com/display/SD/MTU+9000"
			)
		),

		"summary" => array(
			"id" => 14059,
			"text" => "The Summary provides the ability to create the volumes configured in the previous step. If you see changes needing to be made prior to selecting “Accept an Deploy”, select the “Back” button to return to the Volume Configuration screen.

				Selecting “Accept and Deploy” will result in the listed volumes on this page to be created based on the specifications provided during the volume configuration. Once deployment has completed successfully, the volume will listed will display its status as well as any applicable mountpoints created.

				Due to ZFS overhead, the resulting disk devices created will be slightly larger than specified. It should also be noted the resulting volumes size will be slightly different as well.

				“Advanced Deployment Information” provides additional details about the Storage Pools and Disks created.

				Selecting “Close” will exit the Getting Started Wizard.",
			"doc_hl" => array(),
			"kb_hl" => array(),
			"video_hl" => array(
				"Cloud Essentials Getting Started Wizard Overview" => "https://www.youtube.com/embed/fdIkYba5pvg"
			),
			"related_hl" => array()
		)	
	
	);
	public $help_dashboard = array();
	public $help_diskdevices = array(
		
		"add_disk_type" => array(
			"id" => 4779,
			"text" => "Choose the type of storage for the disk being added.
						
						- Select Cloud Disk Extender for Object Storage (S3). Object storage is ideal for unstructured data like music, images, and video files. It is also a good selection for storage of backup files, database dumps and log files as well as large data sets that are not accessed frequently and do not change frequently.
						
						- Select Amazon EBS Disk for Block Storage. SSD Block storage is ideal for mission-critical applications and databases that require consistent I/O performance and low-latency connectivity. You can use block storage to create software RAID Volumes during the creation of Storage Pools. Magnetic (HDD) drives are also available as a cheaper alternative, but as with object storage, provide limited performance (but at a lower cost).",
			"doc_hl" => array(
				"Adding Cloud Disk Extenders" => "https://docs.softnas.com/display/SD/Adding+Cloud+Disk+Extenders",
				"Add S3 Cloud Disk" => "https://docs.softnas.com/display/SD/Add+S3+Cloud+Disk",
				"Adding Amazon EBS Disks" => "https://docs.softnas.com/display/SD/Adding+Amazon+EBS+Disks"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Getting Started Checklist - Create AWS Disk Devices" => "https://vimeo.com/290764331/5a37ad1118"
			),
			"related_hl" => array(
				"Best Practices - S3 Cloud Disk" => "https://docs.softnas.com/display/SD/S3+Cloud+Disk+Best+Practices",
				"SoftNAS Cloud S3 Disk Overview" => "https://docs.softnas.com/pages/viewpage.action?pageId=65703",
				"Amazon S3 Storage" => "https://aws.amazon.com/s3/storage-classes/",
				"Amazon EBS Storage" => "http://docs.aws.amazon.com/AWSEC2/latest/UserGuide/AmazonEBS.html"
			)
		),
		
		"combo_add_disk_extender" =>  array(
			"id" => 4819,
			"text" => "Select the desired type of Object Storage.
				
				- For SoftNAS Cloud (R) instances running in AWS, select Amazon Web Services S3.
				- For SoftNAS Cloud (R) instances running in Azure, select Azure Blob.
				- For SoftNAS Cloud (R) instances running on other platforms, including VMWare, select the correct type.
				- If you do not see your specific Object Storage type, you can use “Self Configured”.",
			"doc_hl" => array(
				"Adding Cloud Disk Extenders" => "https://docs.softnas.com/display/SD/Adding+Cloud+Disk+Extenders",
				"Adding Object Storage via the SoftNAS UI" => "https://docs.softnas.com/display/SD/Adding+Object+Storage+via+the+SoftNAS+UI"
			),
			"kb_hl" => array(
				"Best Practices - S3 Compatible Cloud Disk Extender" => "https://softnas.com/helpdesk/index.php?/Knowledgebase/Article/View/131/0/softnas-kb--best-practices--s3-compatible-cloud-disk-extender-best-practices"
			),
			"video_hl" => array(
				"SoftNAS Cloud on AWS: Navigating your Instance" => "https:https://vimeo.com/290765172/b293be8638"
			),
			"related_hl" => array(
				"Amazon S3 Storage Classes" => "https://aws.amazon.com/s3/storage-classes/",
				"Understanding Amazon S3 Storage" => "http://docs.aws.amazon.com/AWSEC2/latest/UserGuide/AmazonS3.html",
				"Understanding Amazon EBS Storage Classes" => "http://docs.aws.amazon.com/AWSEC2/latest/UserGuide/AmazonEBS.html"
			)
		),
		"s3_aws_credentials" => array(
			"id" => 4820,
			"text" => "The AWS IAM Roles are created and managed through the AWS Identity and Access Management (IAM) Console. You use IAM to control who can use your AWS resources (authentication) and what resources they can use and in what manner. These keys are required to allow S3 storage be managed by SoftNAS Cloud®.
						
						For more information, refer to the “Other Resources” below.",
			"doc_hl" => array(
				"Add S3 Cloud Disk" => "https://docs.softnas.com/display/SD/Add+S3+Cloud+Disk"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Creating a new AWS Access Key ID" => "https://vimeo.com/295021260/8dafe889a5"
			),
			"related_hl" => array(
				"Setting Up Amazon SQS" => "http://docs.aws.amazon.com/AWSSimpleQueueService/latest/SQSDeveloperGuide/sqs-setting-up.html#sqs-creating-aws-account",
				"About Access Keys" => "https://aws.amazon.com/developers/access-keys/",
				"AWS Security Credentials" => "http://docs.aws.amazon.com/general/latest/gr/aws-security-credentials.html",
				"AWS IAM Introduction" => "https://youtu.be/Ul6FW4UANGc",
				"AWS Identity and Access Management Documentation" => "https://aws.amazon.com/documentation/iam/"
			)
		),
		"s3_aws_disk_size" => array(
			"id" => 4828,
			"text" => "This value can be between 1 GB and 4095 TB (4 petabytes). This is the maximum cloud disk size for the device. As cloud disks are thin provisioned, there are no Amazon S3 storage costs until data is stored in a SoftNAS Cloud® storage pool and volume.

						When choosing a Maximum Disk Size, please keep in mind that a SoftNAS Cloud® license will be required for the maximum amount of storage planned for use. VMs will consume approximately 30GB of disk space.",
			"doc_hl" => array(
				"Adding Cloud Disk Extenders" => "https://docs.softnas.com/display/SD/Adding+Cloud+Disk+Extenders"
			),
			"kb_hl" => array(),
			"video_hl" => array(),
			"related_hl" => array(
				"Best Practices - S3 Cloud Disk" => "https://docs.softnas.com/display/SD/S3+Cloud+Disk+Best+Practices"
			)
		),
		"s3_aws_encryption" => array(
			"id" => 4869,
			"text" => "Check this box to apply encryption. AES-256 CBC encryption is used to provide a balance of performance and security strength.

						Note that encryption adds performance overhead; you may wish to consider additional processing power (vCPU).",
			"doc_hl" => array(),
			"kb_hl" => array(),
			"video_hl" => array(),
			"related_hl" => array(
				"Understanding Advanced Encryption Standard (AES)" => "https://en.wikipedia.org/wiki/Advanced_Encryption_Standard"
			)
		),
		"ebs_delete_on_term" => array(
			"id" => 4844,
			"text" => "If checked, when the AWS instance is terminated the disk(s) will automatically be deleted. This is helpful when performing tests and other temporary exercises.",
			"doc_hl" => array(
				"Adding Amazon EBS Disks" => "https://docs.softnas.com/display/SD/Adding+Amazon+EBS+Disks"
			),
			"kb_hl" => array(),
			"video_hl" => array(),
			"related_hl" => array(
				"Preserving Amazon EBS Volumes on Instance Termination" => "http://docs.aws.amazon.com/AWSEC2/latest/UserGuide/terminating-instances.html#preserving-volumes-on-termination"
			)
		),
		"ebs_disk_type" => array(
			"id" => 4838,
			"text" => "Select the EBS device to be managed by SoftNAS.
						- For workloads demanding high performance and guaranteed IOPS, consider Provisioned IOPS (SSD).
						- For workloads demanding high performance, consider General Purpose (SSD).
						- For workloads not requiring high performance, consider Magnetic or HDD.
						For additional information, refer to the links below:",
			"doc_hl" => array(
				"Adding Amazon EBS Disks" => "https://docs.softnas.com/display/SD/Adding+Amazon+EBS+Disks"
			),
			"kb_hl" => array(
				"Best Practices – AWS and EBS Instance Selection" => "https://www.softnas.com/helpdesk/index.php?/Knowledgebase/Article/View/93/7/softnas-kb--best-practices--aws-and-ebs-instance-selection"
			),
			"video_hl" => array(
				"Getting Started Checklist - Creating an EBS Disk" => "https://vimeo.com/290764331/5a37ad1118"
			),
			"related_hl" => array(
				"Understanding Amazon EBS Volume Types" => "http://docs.aws.amazon.com/AWSEC2/latest/UserGuide/EBSVolumeTypes.html"
			)
		),
		"ebs_disk_encrypt" => array(
			"id" => 4839,
			"text" => "Check this option and provide a Disk Password to encrypt the contents of the EBS disk to ensure its contents cannot be accessed, except by those with the appropriate credentials. Amazon EBS encryption ensures the following data is encrypted:
						- Data at rest inside the volume
						- All data moving between the volume and the instance
						- All snapshots created from the volume",
			"doc_hl" => array(
				"Adding Amazon EBS Disks" => "https://docs.softnas.com/display/SD/Adding+Amazon+EBS+Disks"
			),
			"kb_hl" => array(),
			"video_hl" => array(),
			"related_hl" => array(
				"Amazon EBS Encryption" => "http://docs.aws.amazon.com/AWSEC2/latest/UserGuide/EBSEncryption.html",
				"AWS Key Management Services (KMS)" => "https://www.youtube.com/watch?v=-5MPXHvKDnc"
			)
		),
		"extender_credentials" => array(
			"id" => 5014,
			"text" => "In order to add storage disks of any variety to your SoftNAS instance, you will need to create at least one storage account. Creating Microsoft Block Storage or Blob (Object Storage) disks require different storage account types. When creating your blob storage account, you can determine whether the account will provision hot blob storage, or cool blob storage. Never mix the two in the same storage pool.",
			"doc_hl" => array(
				"Creating Storage Accounts" => "https://docs.softnas.com/display/SD/Creating+Storage+Accounts"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"How To: Understanding and Adding Blob Storage for SoftNAS Cloud on Azure" => "https://www.youtube.com/embed/gQ8J9Fyfm-U",
				"How To: Using Hot and Cool Blob Storage and Multiple Blob Storage Accounts for SoftNAS Cloud" => "https://www.youtube.com/embed/vq0vt4xl4Ns"
			),
			"related_hl" => array(
				"About Azure storage accounts" => "https://docs.microsoft.com/en-us/azure/storage/storage-create-storage-account"
			)
		),
		"extender_disk_size" => array(
			"id" => 5015,
			"text" => "The maximum size of a single Azure Blob is limited to 500 TB. Thin-provisioning occurs automatically for these devices. It is possible to leverage multiple storage accounts to create pools and volumes of up to 16 PB in size.",
			"doc_hl" => array(
				"Adding Object Storage via the SoftNAS UI" => "https://docs.softnas.com/display/SD/Adding+Object+Storage+via+the+SoftNAS+UI"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"How To: Understanding and Adding Blob Storage for SoftNAS Cloud on Azure" => "https://www.youtube.com/embed/gQ8J9Fyfm-U"
			),
			"related_hl" => array(
				"Sneak Peek: See How SoftNAS Reduces Storage Costs Using Azure Blob Storage While Increasing Scale and Security" => "https://www.softnas.com/wp/blog-blob-storage-sneak-peek/"
			)
		),
		"msft_credentials" => array(
			"id" => 5016,
			"text" => "In order to add storage disks of any variety to your SoftNAS instance, you will need to have a valid Azure username or Service Principal as well as their associated password.
						Creating Microsoft Blob (Object Storage) disks also requires a storage account be created for each type of Blob storage to be used. Storage Accounts are created in the Azure Portal.",
			"doc_hl" => array(
				"Creating Storage Accounts" => "https://docs.softnas.com/display/SD/Creating+Storage+Accounts",
				"Creating an Identity for Service Principals in Azure" => "https://docs.softnas.com/display/SD/Creating+an+Identity+for+Service+Principals+in+Azure",
				"Adding Block Storage via the SoftNAS UI" => "https://docs.softnas.com/display/SD/Adding+Block+Storage+via+the+SoftNAS+UI"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Creating Azure Storage Accounts" => "https://vimeo.com/323568282",
				"Azure Service Principal Set-up" => "https://vimeo.com/321600002"
			),
			"related_hl" => array(
				"About Azure Storage accounts" => "https://docs.microsoft.com/en-us/azure/storage/storage-create-storage-account",
				"Understanding Block and Object Storage on Azure - Video" => "https://vimeo.com/311905213"
			)
		),
		"msft_disk_options" => array(
			"id" => 5017,
			"text" => "Azure managed disks are block-level storage volumes that are managed by Azure and used with Azure Virtual Machines. Managed disks are like a physical disk in an on-premises server but virtualized. With managed disks, all you have to do is specify the disk size, the disk type, and provision the disk. Once you provision the disk, Azure handles the rest.
						
						Some of the benefits of managed disks are:
							<ul>
							<li>Managed disks are designed for 99.999% availability</li>
							<li>Integrated with availability sets</li>
							<li>Integration with availability zones</li>
							<li>Azure Backup support</li>
							<li>Granular access control</li>
							</ul>
						
						The available types of disks are ultra disks, premium solid-state drives (SSD), standard SSDs, and standard hard disk drives (HDD).
						
						<b>Ultra Disk</b> is Azure's next generation high performance Solid State Drive (SSD) with configurable performance attributes that provides the lowest latency and consistent high IOPS/throughput. Ultra Disk offers unprecedented and extremely scalable performance with sub-millisecond latency. As a customer you can start small on IOPS and throughput and adjust your performance as your workload becomes more IO intensive.
						
						<b>Premium SSD</b> Managed Disks are high performance Solid State Drive (SSD) based Storage designed to support I/O intensive workloads with significantly high throughput and low latency. With Premium SSD Managed Disks, you can provision a persistent disk and configure its size and performance characteristics.
						
						<b>Standard SSD</b> Managed Disks, a low-cost SSD offering, are optimized for test and entry-level production workloads requiring consistent latency. Standard SSD Managed Disks can also be used for big data workloads that require high throughput.
						
						<b>Standard HDD</b> Managed Disks use Hard Disk Drive (HDD) based Storage media. They are best suited for dev/test and other infrequent access workloads that are less sensitive to performance variability.
						
						In general the disk sizes span from 4 GiB up to 32 TiB. For additional details including size and pricing. please refer to the Managed Disk Pricing.",
			"doc_hl" => array(
				"Adding Block Storage via the SoftNAS UI" => "https://docs.softnas.com/display/SD/Adding+Block+Storage+via+the+SoftNAS+UI",
				"Creating an Identity for Service Principals in Azure" => "https://docs.softnas.com/display/SD/Creating+an+Identity+for+Service+Principals+in+Azure"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Understanding Block and Object Storage on Azure" => "https://vimeo.com/311905213",
				"Azure Service Principal Set-up" => "https://vimeo.com/321600002"
			),
			"related_hl" => array(
				"Migrating unmanaged disks to managed disks" => "https://vimeo.com/389302886",
				"Managed Disk pricing" => "https://azure.microsoft.com/en-us/pricing/details/managed-disks/",
				"Introduction to Azure managed disks" => "https://docs.microsoft.com/en-us/azure/virtual-machines/windows/managed-disks-overview",
				"Frequently asked questions about Azure IaaS VM disks and managed and unmanaged premium disks" => "https://docs.microsoft.com/en-us/azure/virtual-machines/windows/faq-for-disks"
			)
		),
		"import_diskdevice" => array(
			"id" => 5018,
			"text" => "You may need to migrate data disks, along with their associated storage pools and volumes, from one SoftNAS instance to another. Examples of such cases may be:
					
					- Copy or move data from one SoftNAS VM to a different site or location.
					- Copy or move data from one SoftNAS instance on Amazon EC2 to a different region (different data center).
					- Restore a data image from backup (not common, but one of the valid use cases one should always be prepared to handle). For example, you may have EBS volume image snapshots saved on Amazon EC2, and want to migrate a backup copy into a new SoftNAS instance.",
			"doc_hl" => array(
				"How to Migrate Data Disks to a New SoftNAS VM" => "https://docs.softnas.com/display/SD/How+to+Migrate+Data+Disks+to+a+New+SoftNAS+VM",
				"Importing S3 Disks" => "https://docs.softnas.com/display/SD/Importing+S3+Disks"
			),
			"kb_hl" => array(
				"S3 Import" => "https://www.softnas.com/helpdesk/index.php?/Knowledgebase/Article/View/9/0/softnas-kb-s3-import"
			),
			"video_hl" => array(),
			"related_hl" => array()
		),
		"tiered_storage_configuration" => array(
			"id" => 14068,
			"text" => "You are able to change the Maximum block age as well as the Reverse migration grace period of the tiers within the select Tiered device. Select the parameter you wish to change for a specific Tiered LUN and enter the change.
						
						Seconds Time Conversion Table
						3,600 = 1 hour
						43,200 = 12 hours
						86,400 = 24 hours (1 day)
						432,000 = 120 hours (5 days)
						604,800 = 168 hours (7 days)
						2,592,000 = 720 hours (30 days)",
			"doc_hl" => array(
				"SmartTiers™ Configuration" => "https://docs.softnas.com/pages/viewpage.action?pageId=18808879"
			),
			"kb_hl" => array(),
			"video_hl" => array(),
			"related_hl" => array()
		),
		"tiered_storage_device_options" => array(
			"id" => 14069,
			"text" => "The Migration Interval is the frequency the SmartTiers™ will be scanned to determine if blocks of data need to be migrated between the tiers within the specific Tiered Device being displayed.
						
						You can set a \"high water mark\" for the hot tier by providing a percentage in \"Hot tier storage threshold (%)\".
						
						Alternate block age (seconds) is used in when the \"Hot tier storage threshold\" has been met. This value will override the Maximum Block Age value specified.
						
						Migration schedule enables you to schedule the daily time for when migration scans may occur. Scan frequency uses the specified Migration interval value. This is based on the timezone configured for the instance. This is based on a 24 hour format (hh:mm)",
			"doc_hl" => array(
				"SmartTiers™ Configuration" => "https://docs.softnas.com/pages/viewpage.action?pageId=18808879"
			),
			"kb_hl" => array(),
			"video_hl" => array(),
			"related_hl" => array()
		)
	);
	public $help_flexfiles = array(
		"flexfiles_config" => array(
			"id" => 5307,
			"text" => "The Source Node requires access to the Target Node through ports 9443 and 8081 to perform the data flow through the Fuusion. You need to add the Source Node's IP address to these ports in the Target Node's Network Security Group with /32 to restrict access to only this IP address.
						
						\"This Fuusion Node\" is the IP address for this node. This will be used to establish communication between the nodes.
						
						\"Web UI Port\" is the port used to access the CloudFabric UI. It is used by the CloudFabric to set up a flow, as well as users to create custom flows.
						
						\"Data Port\" is the port used to transfer data between Fuusion nodes.
						
						\"Architect Admin user name\" is the user name of the user granted administrator access in the Fuusion Architect.

						Note: Configuring the Fuusion Settings needs to be performed on all Fuusion Controllers and Nodes being used.",
			"doc_hl" => array(
				"Configuring Fuusion Settings" => "https://docs.softnas.com/display/BF/Configuring+Settings",
				"Configuring Ports and Security Groups for Lift and Shift" => "https://docs.softnas.com/display/SD/Configuring+Ports+and+Security+Groups+for+Lift+and+Shift"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Setting up the AWS Network Security Groups" => "https://vimeo.com/286754255",
				"Setting up the Azure Network Security Group" => "https://vimeo.com/289102733/8d2dffffe8"
			),
			"related_hl" => array(
				"Setting up the VMware Network Security Groups" => "https://vimeo.com/286801636",
				"Firewall and Security Group Configuration Overview" => "https://vimeo.com/290753353"
			)
		),
		"site_to_site_config" => array(
			"id" => 18961,
			"text" => "Before Fuusion nodes are able to interact with each other, certificates must be exchanged and validated. Specific inbound port rules will need to be configured within each of their Network Security Groups prior. The inbound port rules needing to be configured are 9443 and 8081 using the TCP protocol. If you plan to use UltraFast as well, an inbound port rule 8888 for both TCP and UDP will also need to be configured.
						
						It is a Buurst \"Best Practice\" recommendation to \"lock down\" these inbound rules to a specific IP address in order to further increase security.
						
						For Site-to-Site settings, before the two Fuusion node's service can communicate to each, a trust needs to be established and so certificates needs to be exchanged between this Fuusion node and a specified Remote Fuusion node",
			"doc_hl" => array(
				"Configuring Ports and Protocols for AWS" => "https://docs.softnas.com/display/BF/Configuring+Ports+and+Protocols+for+AWS",
				"Configuring Ports and Protocols for Azure" => "https://docs.softnas.com/display/BF/Configuring+Ports+and+Security+Groups+for+Azure",
				"Configuring Ports and Protocols for On-Premise Deployments" => "https://docs.softnas.com/display/BF/Configuring+Ports+and+Protocols+for+On-Premise+Deployments"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Setting up the AWS Network Security Groups" => "https://vimeo.com/286754255",
				"Setting up the Azure Network Security Group" => "https://vimeo.com/289102733"
			),
			"related_hl" => array(
				"Setting up the VMware Network Security Groups" => "https://vimeo.com/286801636",
				"Firewall and Security Group Configuration Overview" => "https://vimeo.com/290753353"
			)
		),
		"fuusion_ultrafast_connection" => array(
			"id" => 18962,
			"text" => "When the \"Use UltraFast Connection\" is checked and the \"Configure Fuusion\" radio button is clicked, the Fuusion service will be reconfigured to use inbound port 8888 with UDP protocol for traffic between this Fuusion node and a remote Fuusion node.
						
						In order to use UltraFast, it is required the Network Security Group be updated. You need to add an inbound port rule for 8888 for both UDP and TCP protocols. The UDP protocol will be used specifically for moving data between the Fuusion nodes. Configuring the inbound port rule for 8888 using the TCP protocol is specifically used to test the performance between the the Fuusion nodes.
						
						It is a Buurst \"Best Practice\" recommendation to \"lock down\" these inbound rules to a specific IP address in order to further increase security.",
			"doc_hl" => array(
				"Configuring Fuusion Settings" => "https://docs.softnas.com/display/BF/Configuring+Settings",
				"Configuring Ports and Protocols for AWS" => "https://docs.softnas.com/display/BF/Configuring+Ports+and+Security+Groups+for+AWS",
				"Configuring Ports and Protocols for Azure" => "https://docs.softnas.com/display/BF/Configuring+Ports+and+Security+Groups+for+Azure"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Setting up the AWS Network Security Groups" => "https://vimeo.com/286754255",
				"Setting up the Azure Network Security Group" => "https://vimeo.com/289102733"
			),
			"related_hl" => array(
				"Setting up the VMware Network Security Groups" => "https://vimeo.com/286801636",
				"Firewall and Security Group Configuration Overview" => "https://vimeo.com/290753353",
				"Configuring Ports and Protocols for On-Premise Deployments" => "https://docs.softnas.com/display/BF/Configuring+Ports+and+Protocols+for+On-Premise+Deployments"
			)
		),
		"repository_config" => array(
			"id" => 18121,
			"text" => "The Fuusion Repository default location is on the root device. Moving the Fuusion Repository from the root device to another block device needs to be performed to help minimize the probability of the root device to filling up due to logs created by the use of Fuusion.
						
						The size of the new Fuusion Repository needs to be at least 50 GiBs in size.
						
						If you are using on-prem as your source device, you will need to create the new location prior to setting the path here.
						
						<b>Note: It is recommended you create this repository on a supported encrypted device to protect any sensitive details that may be retained within it.</b>
						
						For VMware:
						1. Create a new disk and add it to the VMware instance (this is outside the scope of Fuusion)
						2. Partition the disk and mount it to the desired path ( /fuusion/repository )
						3. Navigate to the Fuusion settings tab
						4. In the repository location enter the mount point and click on \"Configure Repository\"",
			"doc_hl" => array(
				"Configuring Fuusion Settings" => "https://docs.softnas.com/display/BF/Configuring+Fuusion+Settings"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Configuring the Fuusion Settings" => "https://vimeo.com/433748017"
			),
			"related_hl" => array()
		),
		"runtime_config" => array(
			"id" => 14173,
			"text" => "Increase the Maximum concurrent thread count used within the Apache NiFi from 10 to 20. This setting will help to reduce bottlenecks during data movement between Fuusion nodes. Once the data movement has been initiated between Fuusion nodes, additional tuning of this parameter can be done within the NiFi UI.
						
						Note: Increasing this value does impact CPU performance and may require to be customized to the specific Instance/Virtual Machine being used.",
			"doc_hl" => array(
				"Configuring Fuusion Settings" => "https://docs.softnas.com/display/SD/Configuring+FlexFile+Settings"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Configuring Fuusion Settings" => "https://vimeo.com/289287062/0342512e38"
			),
			"related_hl" => array()
		),
		"create_flow_cloud_target_platform" => array(
			"id" => 5978,
			"text" => "Select the platform you want to shift the volumes to in this Lift and Shift flow. Authorized access to the platform selected will be required as well as valid credentials to create the target storage.

					- Amazon Web Services (AWS) will require the Access Key ID and Secret Access Key.
					- Microsoft Azure will require the Azure username and password.
					- VMware will require the Target Node IP as well as username and password.",
			"doc_hl" => array(
				"Lift and Shift of a Volume from Source to Target" => "https://docs.softnas.com/display/SD/Lift+and+Shift+of+a+Volume+from+Source+to+Target",
				"Lift and Shift File Migration" => "https://docs.softnas.com/pages/viewpage.action?pageId=8454688"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Lift & Shift Technical Overview" => "https://vimeo.com/288211075"
			),
			"related_hl" => array()
		),
		"create_flow_name" => array(
			"id" => 5977,
			"text" => "Select a name for the Lift and Shift flow being created. The name will be displayed during the create process as well as on the Lift and Shift summary page. This will enable a method to easily identify a flow from the list presented. Alphanumeric, spaces, and \"-\" are supported, no special characters are supported.",
			"doc_hl" => array(
				"Lift and Shift of a Volume from Source to Target" => "https://docs.softnas.com/display/SD/Lift+and+Shift+of+a+Volume+from+Source+to+Target",
				"Lift and Shift File Migration" => "https://docs.softnas.com/pages/viewpage.action?pageId=8454688"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Lift & Shift Technical Overview" => "https://vimeo.com/288211075"
			),
			"related_hl" => array()
		),
		"create_flow_run_lift_shift" => array(
			"id" => 5976,
			"text" => "During this phase, the final verification of communication and resource availability on the Target Node.
						
						Once the verification has completed, the Finish button will be available. Selection of this will finalize the CloudFabric configuration and will initiate the Lift and Shift process.",
			"doc_hl" => array(
				"Lift and Shift of a Volume from Source to Target" => "https://docs.softnas.com/display/SD/Lift+and+Shift+of+a+Volume+from+Source+to+Target",
				"Lift and Shift File Migration" => "https://docs.softnas.com/pages/viewpage.action?pageId=8454688"
			),
			"kb_hl" => array(),
			"video_hl" => array(),
			"related_hl" => array()
		),
		"create_flow_map_volumes" => array(
			"id" => 5975,
			"text" => "If you used the \"Create Target Storage\" wizard, the Source and Target volumes will automatically be mounted.
						
						When using the Advanced Configuration method, you will need to select the Source and target volumes to be used for this Lift and Shift flow.
						
						Continuous Synchronization is automatically set but can be turned off by deselecting the box and updating the parameters specified.",
			"doc_hl" => array(
				"Lift and Shift of a Volume from Source to Target" => "https://docs.softnas.com/display/SD/Lift+and+Shift+of+a+Volume+from+Source+to+Target",
				"Lift and Shift File Migration" => "https://docs.softnas.com/pages/viewpage.action?pageId=8454688"
			),
			"kb_hl" => array(),
			"video_hl" => array(),
			"related_hl" => array()
		),
		"create_flow_advanced_configuration" => array(
			"id" => 5974,
			"text" => "The Advanced Configuration allows for more control over what is created on the Target Node.
						1) Create the disk devices for the Target
						2) Create the pools for the Target
						3) Create the volumes for the Target.
						
						After these steps are complete, select Next to proceed to Step 5.",
			"doc_hl" => array(
				"Lift and Shift of a Volume from Source to Target" => "https://docs.softnas.com/display/SD/Lift+and+Shift+of+a+Volume+from+Source+to+Target",
				"Lift and Shift File Migration" => "https://docs.softnas.com/pages/viewpage.action?pageId=8454688"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Creating Target Storage using the Advanced Configuration option" => "https://vimeo.com/290748215/f29c70c5d4"
			),
			"related_hl" => array()
		),
		"create_volume_for_every_source" => array(
			"id" => 15390,
			"text" => "Select this if you want to have a new target volume created for every source selected to be migrated. When it is checked, it functions the same way as volume lift and shift where a target volume is created automatically for every source. If its unchecked, only 1 volume will be created where user can specify the base name. If user specified an existing name, an auto suffix will be added to the name.",
			"doc_hl" => array(
				"Lift and Shift of a Volume from Source to Target" => "https://docs.softnas.com/display/SD/Lift+and+Shift+of+a+Volume+from+Source+to+Target"
			),
			"kb_hl" => array(),
			"video_hl" => array(),
			"related_hl" => array()
		),
		"create_flow_configure_target_volumes" => array(
			"id" => 5971,
			"text" => "Lift and Shift provides 2 different approaches to creating the disks, pools and volumes on the target node.
						
						- The \"Create Target Storage\" is a wizard which will automatically configure the target storage and set up the \"Lift from\" and \"Shift to\" relationship of the mount points selected.
						- The \"Advanced configuration\" allows you to control the specific details about the disks, storage pools, and volumes utilizing the same interface as available from the Storage Administration panel. You will also need to establish the \"Lift from\" and \"Shift to\" relationship of the source and target mount points.",
			"doc_hl" => array(
				"Lift and Shift of a Volume from Source to Target" => "https://docs.softnas.com/display/SD/Lift+and+Shift+of+a+Volume+from+Source+to+Target",
				"Lift and Shift File Migration" => "https://docs.softnas.com/pages/viewpage.action?pageId=8454688"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Creating Target Storage using the Create Target Storage Wizard" => "https://vimeo.com/290758369/84bdf0d035",
				"Creating Target Storage using the Advanced Configuration option" => "https://vimeo.com/290748215/f29c70c5d4"
			),
			"related_hl" => array()
		),
		"create_flow_select_volumes" => array(
			"id" => 5970,
			"text" => "Select the volumes to be copied to the Target Node. The volumes can be either NFS or CIFS Shares residing on this Source Node or an external FileServer if configured.",
			"doc_hl" => array(
				"Lift and Shift of a Volume from Source to Target" => "https://docs.softnas.com/display/SD/Lift+and+Shift+of+a+Volume+from+Source+to+Target",
				"Lift and Shift File Migration" => "https://docs.softnas.com/pages/viewpage.action?pageId=8454688"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Using the Advanced Configuration Option" => "https://vimeo.com/290748215/f29c70c5d4",
				"Using the Create Target Storage Option" => "https://vimeo.com/290758369/84bdf0d035"
			),
			"related_hl" => array(
				"Lift and Shift Technical Overview" => "https://vimeo.com/288211075"
			)
		),
		"create_flow_source_fileserver" => array(
			"id" => 5969,
			"text" => "Enter the external FileServer DNS or IP Address to obtain NFS or CIFS Shares to be shifted to the Target Node. This allows data residing on another NAS device to serve as the source. When using CIFS, you will need to provide specific credentials in order to have the shares displayed. The \"Mount Volumes\" will become active allowing you to select specific volumes to be included in this Lift and Shift Flow.",
			"doc_hl" => array(
				"Lift and Shift of a Volume from Source to Target" => "https://docs.softnas.com/display/SD/Lift+and+Shift+of+a+Volume+from+Source+to+Target"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Using an External FileServer" => "https://vimeo.com/290759305/3de8834bb7"
			),
			"related_hl" => array()
		),
		"create_flow_mount_options" => array(
			"id" => 5972,
			"text" => "Enter the external FileServer DNS or IP Address as well as username and password. Select \"Mount Volumes\" to have the available NFS and/or CIFS Volumes to be added to the \"Select Volumes\" list.",
			"doc_hl" => array(
				"Lift and Shift of a Volume from Source to Target" => "https://docs.softnas.com/display/SD/Lift+and+Shift+of+a+Volume+from+Source+to+Target",
				"Lift and Shift File Migration" => "https://docs.softnas.com/pages/viewpage.action?pageId=8454688"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Using an External File Server with Lift & Shift" => "https://vimeo.com/290759305/3de8834bb7",
				"Using the Advanced Configuration Option" => "https://vimeo.com/290748215/f29c70c5d4"
			),
			"related_hl" => array(
				"Using the Create Target Storage Option" => "https://vimeo.com/290758369/84bdf0d035",
				"Lift & Shift Overview" => "https://vimeo.com/288211075"
			)
		),
		"lift_shift" => array(
			"id" => 5968,
			"text" => "This is the Lift and Shift summary page used for the creation and management of data flows between Source and Target Nodes.",
			"doc_hl" => array(
				"Lift and Shift of a Volume from Source to Target" => "https://docs.softnas.com/display/SD/Lift+and+Shift+of+a+Volume+from+Source+to+Target",
				"Lift and Shift™ File Migration" => "https://docs.softnas.com/pages/viewpage.action?pageId=8454688"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Lift and Shift Overview" => "https://vimeo.com/290762697/3bee988f84",
				"Using an External File Server" => "https://vimeo.com/290759305/3de8834bb7"
			),
			"related_hl" => array()
		),
		"create_flow_azure_cloud_credentials" => array(
			"id" => 5967,
			"text" => "Enter the Azure Cloud account user and password. This information will be used later in this process to create the Target Node's disks, pool, and volumes for this specific Lift and Shift Flow.",
			"doc_hl" => array(
				"Lift and Shift of a Volume from Source to Target" => "https://docs.softnas.com/display/SD/Lift+and+Shift+of+a+Volume+from+Source+to+Target",
				"Creating Storage Accounts" => "https://docs.softnas.com/display/SD/Creating+Storage+Accounts"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"How To: Creating your SoftNAS Cloud Instance on Microsoft Azure" => "https://vimeo.com/290752498/37822f7949",
				"How To: Using Hot and Cool Blob Storage and Multiple Blob Storage" => "https://vimeo.com/290760104/93e662a448"
			),
			"related_hl" => array(
				"About Azure Storage Accounts" => "https://docs.microsoft.com/en-us/azure/storage/storage-create-storage-account",
				"Introduction to Microsoft Azure Storage" => "https://docs.microsoft.com/en-us/azure/storage/storage-introduction"
			)
		),
		"create_flow_specify_target_instance" => array(
			"id" => 5966,
			"text" => "Enter the Public IP address, User and Password information for the Target Node being used. Once the appropriate information is entered, you need to select \"Verify login\" to assure credentials entered for the target node are correct. If login is successful, the Next button in the lower right will become active. If the login fails, then verify the information for the target is correct and select \"Verify login\" again.",
			"doc_hl" => array(
				"Lift and Shift of a Volume from Source to Target" => "https://docs.softnas.com/display/SD/Lift+and+Shift+of+a+Volume+from+Source+to+Target",
				"Lift and Shift™ File Migration" => "https://docs.softnas.com/pages/viewpage.action?pageId=8454688",
				"View Fuusion Data Flows through the Fuusion Architect" => "https://docs.softnas.com/pages/viewpage.action?pageId=8455150"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Lift and Shift Overview" => "https://vimeo.com/288211075",
				"Using an External File Server for data to migrate" => "https://vimeo.com/290759305/3de8834bb7"
			),
			"related_hl" => array()
		),
		"create_flow_aws_cloud_credentials" => array(
			"id" => 5964,
			"text" => "Enter the IAM or Amazon Web Services Access Key and Secret Key in order to allow storage creation.",
			"doc_hl" => array(
				"Configuring AWS Identity and Access Management" => "https://docs.softnas.com/display/SD/Configuring+AWS+Identity+and+Access+Management",
				"SoftNAS Cloud Performance Best Practices" => "https://docs.softnas.com/display/SD/Best+Practices"
			),
			"kb_hl" => array(),
			"video_hl" => array(),
			"related_hl" => array(
				"AWS Identity and Access Management Documentation" => "https://aws.amazon.com/documentation/iam/",
				"IAM Roles" => "http://docs.aws.amazon.com/IAM/latest/UserGuide/id_roles.html",
				"Identities (Users, Groups, and Roles)" => "http://docs.aws.amazon.com/IAM/latest/UserGuide/id.html",
				"Setting up an IAM user and sign in to the AWS Management Console using IAM Credentials" => "https://youtube.com/embed/XMi5fXL2Hes"
			)
		),
		"how_pool_size_is_calculated" => array(
			"id" => 5420,
			"text" => "\"Pool Size\" is automatically calculated as 120% of the sum of the sizes of all volumes to transfer rounded to the nearest whole number.",
			"doc_hl" => array(
				"Lift and Shift of a Volume from Source to Target" => "https://docs.softnas.com/display/SD/Lift+and+Shift+of+a+Volume+from+Source+to+Target"
			),
			"kb_hl" => array(),
			"video_hl" => array(),
			"related_hl" => array()
		),
		"create_flow_select_volumes_to_mount_vol_name" => array(
            "id" => 6287,
            "text" => "Enter the \"Export Path\" in the format: \"/export/directory/path\".

                Valid directory path needs to be entered. ",
            "doc_hl" => array(
                "Lift and Shift of a Volume from Source to Target" => "https://docs.softnas.com/display/SD/Lift+and+Shift+of+a+Volume+from+Source+to+Target"
            ),
            "kb_hl" => array(),
            "video_hl" => array(),
            "related_hl" => array()
        ),
		"exclude_filter" => array(
            "id" => 17339,
            "text" => "The Exclude Filter options supports the \"glob\" and \"regex\" syntaxes. Each pattern is matched to the file or directory's path relative to the source root directory. When a match is found, the said directory or file will be excluded in the sync operation.
            			
						<i>Exert from Java™ Platform, Standard Edition 7 API Specification</i>
						
						When the syntax is \"glob\" then the object's relative path is matched using a limited pattern language that resembles regular expressions but with a simpler syntax. For example:
						
						<img src=/softnas/images/quickhelp/quickhelp_17339.png />
						
						The following rules are used to interpret glob patterns:
						
						<ul>
							<li>The * character matches zero or more characters of a name component without crossing directory boundaries.</li>
							<li>The ** characters matches zero or more characters crossing directory boundaries.</li>
							<li>The ? character matches exactly one character of a name component.</li>
							<li>The backslash character (\) is used to escape characters that would otherwise be interpreted as special characters. The expression \\ matches a single backslash and \"\{\" matches a left brace for example.</li>
							<li>The [ ] characters are a bracket expression that match a single character of a name component out of a set of characters. For example, [abc] matches \"a\", \"b\", or \"c\". The hyphen (-) may be used to specify a range so [a-z] specifies a range that matches from \"a\" to \"z\" (inclusive). These forms can be mixed so [abce-g] matches \"a\", \"b\", \"c\", \"e\", \"f\" or \"g\". If the character after the [ is a ! then it is used for negation so [!a-c] matches any character except \"a\", \"b\", or \"c\".</li>
							<li>Within a bracket expression the \"*\", ? and \ characters match themselves. The (-) character matches itself if it is the first character within the brackets, or the first character after the ! if negating.</li>
							<li>The { } characters are a group of subpatterns, where the group matches if any subpattern in the group matches. The \",\" character is used to separate the subpatterns. Groups cannot be nested.</li>
							<li>Leading period/dot characters in file name are treated as regular characters in match operations. For example, the \"*\" glob pattern matches file name \".login\".</li>
						</ul>
						
						All other characters match themselves in an implementation dependent manner. This includes characters representing any name-separators.
						
						The matching of root components is highly implementation-dependent and is not specified.
						
						When the syntax is \"regex\" then the pattern component is a regular expression.
						
						For both the glob and regex syntax's, the matching details, such as whether the matching is case sensitive, are implementation-dependent and therefore not specified.
						
						<b>Parameters:</b>
						syntaxAndPattern - The syntax and pattern
						<b>Returns:</b>
						A path matcher that may be used to match paths against the pattern
						<b>Throws:</b>
						IllegalArgumentException - If the parameter does not take the form: syntax:pattern
						PatternSyntaxException - If the pattern is invalid
						UnsupportedOperationException - If the pattern syntax is not known to the implementation
						
						<b>See Also:</b>
						Files.newDirectoryStream(Path,String) in the the Oracle document on Java File Systems",
            "doc_hl" => array(),
            "kb_hl" => array(),
            "video_hl" => array(
            	"Lift and Shift - Editing Volume Flows" => "https://vimeo.com/392353960"
            ),
            "related_hl" => array(
            	"Java File System getPathMatcher" => "https://docs.oracle.com/javase/7/docs/api/java/nio/file/FileSystem.html#getPathMatcher(java.lang.String)"
            )
        )
	);
	public $help_gettingstarted = array(
		"step1" => array(
			"id" => 15513,
			"text" => "The Network Settings panel is used to administer network interfaces, routing and gateways, hostname, DNS and other network-related configuration.",
			"doc_hl" => array(
				"Configuring Network Settings" => "https://docs.softnas.com/display/SD/Configuring+Network+Settings"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Step 1 - Configure Network Settings and Hostnames" => "https://vimeo.com/302111409/6b67c9dae9"
			),
			"related_hl" => array(
				"Network Interfaces" => "https://docs.softnas.com/display/SD/Configuring+Network+Settings",
				"Routing and Gateways" => "https://docs.softnas.com/display/SD/Routing+and+Gateways",
				"Hostname and DNS Client" => "https://docs.softnas.com/display/SD/Hostname+and+DNS+Client"
			)
		),
		"step2" => array(
			"id" => 15514,
			"text" => "The default password is configured during the creation process for the specific Cloud Platform. SoftNAS Best Practice recommends changing this password upon initial logging into the SoftNAS User Interface. Additional user creation can be accomplished during this step.",
			"doc_hl" => array(
				"Changing Default Passwords" => "https://docs.softnas.com/display/SD/Changing+Default+Passwords"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Step 2 - Set Administrator Passwords" => "https://vimeo.com/302112048/26474b354d"
			),
			"related_hl" => array()
		),
		"step3" => array(
			"id" => 15515,
			"text" => "SoftNAS recommends applying the latest software updates before proceeding. If there is an update available, it is simply a matter of selecting the latest version, and clicking \"Apply Update Now\", and accepting the prompt to confirm the action.",
			"doc_hl" => array(
				"Updating Software" => "https://docs.softnas.com/display/SD/Updating+Software"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Step 3 - Apply Software Updates" => "https://vimeo.com/302113029/53b2a81271"
			),
			"related_hl" => array()
		),
		"step4" => array(
			"id" => 15516,
			"text" => "If you purchased a SoftNAS BYOL (bring your own license), this step is important. If you need a BYOL license, it can be obtained by requesting one either from SoftNAS Sales or an Authorized Consulting Partner. Click Activate New license. If for security reasons you are performing an offline or manual activation, select Manual Activation, and provide the verification code in the field that appears for it.
						
						This step is only required for BYOL.",
			"doc_hl" => array(
				"Updating to Latest Version" => "https://docs.softnas.com/display/SD/Updating+to+Latest+Version"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Step 4 - Activate License" => "https://vimeo.com/302113528/f1bccf417e"
			),
			"related_hl" => array(
				"Contact SoftNAS Sales" => "https://www.softnas.com/wp/contact-us/?utm_source=azure&utm_medium=marketplace&utm_campaign=product-listing&utm_term=description-contact&utm_content=byol",
				"Contact SoftNAS Consulting Partners" => "https://www.softnas.com/wp/consulting-partners/"
			)
		),
		"step5" => array(
			"id" => 15517,
			"text" => "Here is where you have the ability to create Block or Object disk devices that will be used. The ability to create Block or Object disk devices depend on the storage account used during this step. During the \"Add Storage Devices\" step, the account information will need to be provided and will be validated before the disk device type can be created.",
			"doc_hl" => array(
				"Add Disk Device" => "https://docs.softnas.com/display/SD/Add+Disk+Device"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Step 5 - Add Storage Devices" => "https://vimeo.com/302113992/911cce8b48"
			),
			"related_hl" => array(
				"Adding Storage: Amazon Web Services (AWS)" => "https://docs.softnas.com/pages/viewpage.action?pageId=65787",
				"Adding Storage: Microsoft Azure" => "https://docs.softnas.com/display/SD/Adding+Storage%3A+Microsoft+Azure",
				"Adding Storage: VMware vSphere" => "https://docs.softnas.com/display/SD/Adding+Storage%3A+VMware+vSphere"
			)
		),
		"step6" => array(
			"id" => 15518,
			"text" => "If creating disks using the SoftNAS' user interface, partitioning the newly created disks will likely not be necessary. If provisioned during the SoftNAS creation on AWS or Azure, partitioning disk will be necessary. To check if there are disks needing to be partitioned, check the column labelled \"Device Usage\" in the \"Disk Devices\" pane.",
			"doc_hl" => array(
				"Partitioning Disks" => "https://docs.softnas.com/display/SD/Partitioning+Disks"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Step 6 - Partition Storage Devices" => "https://vimeo.com/302114992/f48ce4922a"
			),
			"related_hl" => array()
		),
		"step7" => array(
			"id" => 15519,
			"text" => "The storage pools are used to aggregate disk storage into a large pool of storage that can be conveniently allocated and shared by volumes. It is comprised of devices (object and block storage, or VMDKs) created during the Add Disk Device process, or imported. The storage on these devices is aggregated into a unified pool of storage that can be managed and deployed as a single pool. Each pool provides storage which is then allocated for use into volumes.",
			"doc_hl" => array(
				"Working with Storage Pools" => "https://docs.softnas.com/display/SD/Working+with+Storage+Pools"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Step 7 - Create Storage Pool" => "https://vimeo.com/302115627/9c25877e65"
			),
			"related_hl" => array()
		),
		"step8" => array(
			"id" => 15520,
			"text" => "Volumes provide a way to allocate storage available in a storage pool and share it over the network. Volumes and LUNs are comprise of storage pools that will be accessed from applications or servers using either NFS, CIFS/SMB, AFP or iSCSI. During this step you will create these Volumes and configure how they will be presented to the outside world for use.
						
						Snapshots can also be configured during this step.",
			"doc_hl" => array(
				"Creating and Managing Volumes: All Platforms" => "https://docs.softnas.com/display/SD/Creating+and+Managing+Volumes%3A+All+Platforms",
				"Creating and Managing Volumes: Amazon Web Services (AWS)" => "https://docs.softnas.com/pages/viewpage.action?pageId=65777"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Step 8 - Create Volumes and LUNs" => "https://vimeo.com/302116125/26c5bfc9d2"
			),
			"related_hl" => array()
		),
		"step9" => array(
			"id" => 15521,
			"text" => "Volumes provide a way to allocate storage available in a storage pool and share it over the network. From the Storage Administration pane, located on the left side of the StorageCenter UI, you are able to configure, modify or delete how volumes are shared via NFS, AFP, CIFS/SMB, or iSCSI.",
			"doc_hl" => array(
				"Sharing Volumes: All Platforms" => "https://docs.softnas.com/display/SD/Sharing+Volumes%3A+All+Platforms",
				"Sharing Volumes over a Network" => "https://docs.softnas.com/display/SD/Sharing+Volumes+over+a+Network"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Step 9 - Share Volumes (NFS, AFP, CIFS, iSCSI)" => "https://vimeo.com/302116661/f473d10e0e"
			),
			"related_hl" => array()
		),
		"step10" => array(
			"id" => 15522,
			"text" => "Setting up your administrative email is vital to protect your data. Without the administrator email address, advance warning in the form of logs and alerts would not be received.",
			"doc_hl" => array(
				"Administrator" => "https://docs.softnas.com/display/SD/Administrator",
				"Monitoring" => "https://docs.softnas.com/display/SD/Monitoring",
				"Support Tab" => "https://docs.softnas.com/display/SD/Support+Tab"
			),
			"kb_hl" => array(
				"[SoftNAS KB]: Running and retrieving support reports without email functionality." => "https://docs.softnas.com/pages/viewpage.action?pageId=6783011"
			),
			"video_hl" => array(
				"Step 10 - Set up your notification email" => "https://vimeo.com/302116969/0d0943c444"
			),
			"related_hl" => array()
		)
	
	);
	public $help_iscsitarget = array();
	public $help_license = array();
	public $help_pools = array(
	
		"expand_pool" => array(
			"id" => 4977,
			"text" => "You can expand an existing storage pool by adding additional disk devices to the pool.

						Note: You cannot add devices to an existing RAID array - you must add a new array to create a larger storage aggregate.",
			"doc_hl" => array(
				"Software Raid Considerations" => "https://docs.softnas.com/display/SD/Software+RAID+Considerations",
				"Expanding a Pool" => "https://docs.softnas.com/display/SD/Working+with+Storage+Pools#WorkingwithStoragePools-ExpandingaPool"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				//"SoftNAS Cloud on AWS: Navigating your Instance" => "???"
			),
			"related_hl" => array(
				"Creating and Managing Pools" => "https://docs.softnas.com/display/SD/Creating+and+Managing+Pools"
			)
		),
		"write_log" => array(
			"id" => 4986,
			"text" => "The Write Log provides a cache for incoming writes to be written temporarily to high-speed storage, then later staged to lower-speed spindle-based storage. SSD is recommended for Write Log.
						
						Before you create a read cache or write log, you need to verify that disk drives are available that have not been assigned to other storage pools. You may need to add additional devices to your instance in order to enable this feature.
						
						Important: The Write Log becomes a critical element of your storage pool, so it is highly-recommended to always use a RAID 1 mirror for Write Log (that way, if a write log device fails, you won't risk invalidating your storage pool, as the write log is an integral part of the pool).",
			"doc_hl" => array(
				"Configuring Read Cache and Write Log" => "https://www.softnas.com/docs/softnas/v3/html/configuring_read_cache_and_write_log.html",
				"Creating a Write Log Device" => "https://docs.softnas.com/display/SD/Working+with+Storage+Pools#WorkingwithStoragePools-CreatingaWriteLog"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Creating Read and Write Logs" => "https://vimeo.com/291997853/a63fee3731"
			),
			"related_hl" => array()
		),
		"read_cache" => array(
			"id" => 4987,
			"text" => "SoftNAS Cloud® provides the ability to add Read Cache and Write Log devices to a storage pool. Read Cache provides an additional layer of cache, in addition to RAM memory cache. It is recommended to use SSD storage for read cache and write logs.
						Before you create a read cache or write log, you need to verify that disk drives are available that have not been assigned to other storage pools. You may need to add additional devices to your instance in order to enable this feature.",
			"doc_hl" => array(
				"Configuring Read Cache and Write Log" => "https://docs.softnas.com/display/SD/Configuring+Read+Cache+and+Write+Log",
				"Creating a Read Cache" => "https://docs.softnas.com/display/SD/Working+with+Storage+Pools#WorkingwithStoragePools-CreatingaReadCache"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Creating Read and Write Logs" => "https://vimeo.com/291997853/a63fee3732"
			),
			"related_hl" => array()
		),
		"hot_spare" => array(
			"id" => 4998,
			"text" => "You can add a hot spare for any selected pool. In addition, sparing is automated, with failover to the spare occurring without the need for administrator intervention.
			
						Important - Make sure the device selected to be assigned as a hot spare is the same size as the members of the storage pool it is being assigned to.",
			"doc_hl" => array(
				"Adding an Automated Hot Spare" => "https://docs.softnas.com/display/SD/Working+with+Storage+Pools#WorkingwithStoragePools-AddinganAutomatedHotSpare"
			),
			"kb_hl" => array(),
			"video_hl" => array(),
			"related_hl" => array()
		),
		"combo_sync_mode" => array(
			"id" => 4830,
			"text" => "Sync (Synchronous file system transaction) Modes:
						
						standard: This is the default option. Synchronous transactions are written to the intent log and all drives written are flushed to ensure the data is stable (not cached by device controllers).
						
						always: For the ultra-cautious, every file system transaction is written and flushed to stable storage by a system call return. This obviously has a big performance penalty.
						
						disabled: Synchronous requests are disabled. File system transactions only commit to stable storage on the next DMU transaction group commit which can be many seconds. This option gives the highest performance but increases potential data loss.",
			"doc_hl" => array(
				"Creating a Storage Pool" => "https://docs.softnas.com/display/SD/Create+a+Storage+Pool"
			),
			"kb_hl" => array(),
			"video_hl" => array(),
			"related_hl" => array()
		),
		"combo_raid_level" => array(
			"id" => 4960,
			"text" => "Software RAID is best used in scenarios where raw disk devices are attached directly to SoftNAS. Software RAID is not recommended for object storage and disks behind hardware RAID controllers.
						
						RAID is only supported using Block Storage Disk Devices (AWS EBS or Azure Block) only. Object Disk Devices (AWS S3 or Azure Blob) will only be permitted to be create as JBODs.",
			"doc_hl" => array(
				"Software Raid Considerations" => "https://docs.softnas.com/display/SD/Software+RAID+Considerations",
				"Creating and Managing Pools" => "https://docs.softnas.com/display/SD/Creating+and+Managing+Pools"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Creating a RAID Storage Pool" => "https://vimeo.com/291797350/61b0973780"
			),
			"related_hl" => array()
		),
		// combo_raid_level 4960 duplicates (tier_hot_combo_raid_level, tier_warm_combo_raid_level, tier_cold_combo_raid_level, tier_archive_combo_raid_level):
		"tier_hot_combo_raid_level" => array(
			"id" => 4960,
			"text" => "Software RAID is best used in scenarios where raw disk devices are attached directly to SoftNAS. Software RAID is not recommended for object storage and disks behind hardware RAID controllers.
						
						RAID is only supported using Block Storage Disk Devices (AWS EBS or Azure Block) only. Object Disk Devices (AWS S3 or Azure Blob) will only be permitted to be create as JBODs.",
			"doc_hl" => array(
				"Software Raid Considerations" => "https://docs.softnas.com/display/SD/Software+RAID+Considerations",
				"Creating and Managing Pools" => "https://docs.softnas.com/display/SD/Creating+and+Managing+Pools"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Creating a RAID Storage Pool" => "https://vimeo.com/291797350/61b0973780"
			),
			"related_hl" => array()
		),
		"tier_warm_combo_raid_level" => array(
			"id" => 4960,
			"text" => "Software RAID is best used in scenarios where raw disk devices are attached directly to SoftNAS. Software RAID is not recommended for object storage and disks behind hardware RAID controllers.
						
						RAID is only supported using Block Storage Disk Devices (AWS EBS or Azure Block) only. Object Disk Devices (AWS S3 or Azure Blob) will only be permitted to be create as JBODs.",
			"doc_hl" => array(
				"Software Raid Considerations" => "https://docs.softnas.com/display/SD/Software+RAID+Considerations",
				"Creating and Managing Pools" => "https://docs.softnas.com/display/SD/Creating+and+Managing+Pools"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Creating a RAID Storage Pool" => "https://vimeo.com/291797350/61b0973780"
			),
			"related_hl" => array()
		),
		"tier_cold_combo_raid_level" => array(
			"id" => 4960,
			"text" => "Software RAID is best used in scenarios where raw disk devices are attached directly to SoftNAS. Software RAID is not recommended for object storage and disks behind hardware RAID controllers.
						
						RAID is only supported using Block Storage Disk Devices (AWS EBS or Azure Block) only. Object Disk Devices (AWS S3 or Azure Blob) will only be permitted to be create as JBODs.",
			"doc_hl" => array(
				"Software Raid Considerations" => "https://docs.softnas.com/display/SD/Software+RAID+Considerations",
				"Creating and Managing Pools" => "https://docs.softnas.com/display/SD/Creating+and+Managing+Pools"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Creating a RAID Storage Pool" => "https://vimeo.com/291797350/61b0973780"
			),
			"related_hl" => array()
		),
		"tier_archive_combo_raid_level" => array(
			"id" => 4960,
			"text" => "Software RAID is best used in scenarios where raw disk devices are attached directly to SoftNAS. Software RAID is not recommended for object storage and disks behind hardware RAID controllers.
						
						RAID is only supported using Block Storage Disk Devices (AWS EBS or Azure Block) only. Object Disk Devices (AWS S3 or Azure Blob) will only be permitted to be create as JBODs.",
			"doc_hl" => array(
				"Software Raid Considerations" => "https://docs.softnas.com/display/SD/Software+RAID+Considerations",
				"Creating and Managing Pools" => "https://docs.softnas.com/display/SD/Creating+and+Managing+Pools"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Creating a RAID Storage Pool" => "https://vimeo.com/291797350/61b0973780"
			),
			"related_hl" => array()
		),
		"tier_configs_combo_sync_mode" => array(
			"id" => 4830,
			"text" => "Sync (Synchronous file system transaction) Modes:
						
						standard: This is the default option. Synchronous transactions are written to the intent log and all drives written are flushed to ensure the data is stable (not cached by device controllers).
						
						always: For the ultra-cautious, every file system transaction is written and flushed to stable storage by a system call return. This obviously has a big performance penalty.
						
						disabled: Synchronous requests are disabled. File system transactions only commit to stable storage on the next DMU transaction group commit which can be many seconds. This option gives the highest performance but increases potential data loss.",
			"doc_hl" => array(
				"Creating a Storage Pool" => "https://docs.softnas.com/display/SD/Create+a+Storage+Pool"
			),
			"kb_hl" => array(),
			"video_hl" => array(),
			"related_hl" => array()
		),
		"create_pool_wizard" => array(
			"id" => 5924,
			"text" => "Shared Storage - A single ZFS pool residing on object storage may be shared between HA nodes
						
						SmartTiers - A storage model in which separate storage pools are combined to deliver the appropriate availability of data stored based on its value - a higher frequency of access increases the value of the block. Data is automatically migrated between tiers based on user-defined policy.",
			"doc_hl" => array(
				"Create a Storage Pool" => "https://docs.softnas.com/display/SD/Create+a+Storage+Pool",
				"SmartTiers Configuration" => "https://docs.softnas.com/pages/viewpage.action?pageId=18808879"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Configuring Pools and Volumes" => "https://vimeo.com/290751226/b1d1fdf3ef",
				"Lift and Shift " => "https://vimeo.com/290758369/84bdf0d035"
			),
			"related_hl" => array(
				"Understanding AWS Storage Classes" => "https://aws.amazon.com/s3/storage-classes/",
				"Understanding Amazon S3 Storage" => "http://docs.aws.amazon.com/AWSEC2/latest/UserGuide/AmazonS3.html",
				"Undertanding Amazon EBS Storage Classes" => "http://docs.aws.amazon.com/AWSEC2/latest/UserGuide/AmazonEBS.html",
				"Introduction to Microsoft Azure Storage" => "https://azure.microsoft.com/en-us/documentation/articles/storage-introduction/#blob-storage"
			)
		),
		"create_pool_wizard_tier" => array(
			"id" => 14259,
			"text" => "SoftNAS® SmartTiers™ provides the ability to seamlessly move blocks of data from high performance, low latency storage to more economical, larger capacity storage based on the value of the data. SmartTiers is an intelligent, policy-based, auto-tiering data management feature that automatically migrates aged data from one storage tier to another, while keeping frequently accessed data in high-performance storage.
						
						SmartTiers can cut cloud storage costs by up to 67% by automatically moving older, less-active blocks of data from higher performance, more expensive block storage to less expensive cloud object storage.
						
						SmartTiers is now only a feature of SoftNAS Cloud® Platinum and you must be running Platinum to create new SmartTiers Pools. SmartTiers Pools created using SoftNAS Cloud Enterprise will continue to work as designed.",
			"doc_hl" => array(
				"SmartTiers™ Configuration" => "https://docs.softnas.com/pages/viewpage.action?pageId=18808879",
				"RAM sizing guidance for SmartTiers™" => "https://docs.softnas.com/pages/viewpage.action?pageId=30933018"
			),
			"kb_hl" => array(
				"SmartTiers™  Calculator" => "https://www.softnas.com/wp/calculator/"
			),
			"video_hl" => array(
				"SmartTiers™ Overview" => "https://vimeo.com/287921609"
			),
			"related_hl" => array()
		),
		"primary_tier_hot" => array(
			"id" => 6145,
			"text" => "SmartTiers supports multiple tiers with a limit of 4 allowed to be configured via the StorageCenter UI. The tiers are comprised of disk devices created in the same method as today except with the ability to move blocks of data from various tiers based on a migration policy established by the administrator. These policies will be based on time measurements of seconds. The actual tiers may reside either on premise, solely within the cloud, or a hybrid of both.
						
						- Primary (Hot) Tier consists of high performance devices like on-premise NVMe, AWS Provisioned IOPS, or Azure Premium Storage
						- Secondary (Warm) Tier consist of cloud based SSDs, AWS EBS, or Azure Standard Storage
						- Tertiary (Cool) Tier consists of HDDs
						
						SmartTier Hot - Since all writes initially go to the hot tier, its capacity and performance must be enough to satisfy workload requirements. Types of devices to consider for this tier level is on-premise NVMe, AWS Provisioned IOPS, or Azure Premium Storage. The Hot Tier contains data that is frequently access and is normally retained on-premise and resides on extremely high performance storage devices. Based on the configured user migration policy, blocks of data not access for a set period of time will be automatically migrated to the next level tier consisting on a more economical type of storage device.
						
						Name the SmartTier, select the RAID level and appropriate devices to be assigned and select next to proceed to creating another tier.",
			"doc_hl" => array(
				"SmartTiers™ Configuration" => "https://docs.softnas.com/display/SD/FlexTier+Configuration",
				"Ram Sizing Guidance for SmartTiers™" => "https://docs.softnas.com/pages/viewpage.action?pageId=30933018",
				"Working with Storage Pools" => "https://docs.softnas.com/display/SD/Working+with+Storage+Pools"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"SmartTiers™ Overview" => "https://vimeo.com/290760943/56ce3b9d82",
				"SmartTiers™ Technical Overview" => "https://vimeo.com/287921609"
			),
			"related_hl" => array(
				"Understanding AWS Storage Classes" => "https://aws.amazon.com/s3/storage-classes/",
				"Understanding Amazon S3 Storage" => "http://docs.aws.amazon.com/AWSEC2/latest/UserGuide/AmazonS3.html",
				"Undertanding Amazon EBS Storage Classes" => "http://docs.aws.amazon.com/AWSEC2/latest/UserGuide/AmazonEBS.html",
				"Introduction to Microsoft Azure Storage" => "https://azure.microsoft.com/en-us/documentation/articles/storage-introduction/#blob-storage"
			)
		),
		"secondary_tier_warm" => array(
			"id" => 6146,
			"text" => "The secondary (warm) tier will contain data blocks migrated from the hot tier after the user defined migration policy threshold is reached. The warm tier normally consists of a more economical type of storage device with still retaining reasonable performance characteristics. When blocks of data which reside on the secondary tier is accessed, the read request is automatically addressed and the reference block of data is migrated back to the primary \"hot\" tier. The user has the ability to establish a migration policy for the aging of the secondary tier in the same manner as created for the the primary 'hot' tier where based on a length of time the specific block is not accessed, it will be relocated to the tertiary (cool) tier for storage.
						
						Depending on the SoftNAS license being used, you may be able to continue creating additional tiers by selecting \"Add another tier\". Selecting \"Next\" will take to naming your Tiered Device Name. After naming your device, you will be able to configure the migration policy for each of the tiers.",
			"doc_hl" => array(
				"SmartTiers™ Configuration" => "https://docs.softnas.com/pages/viewpage.action?pageId=18808879",
				"RAM Sizing Guidance for SmartTiers™" => "https://docs.softnas.com/pages/viewpage.action?pageId=30933018"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"SmartTiers™ Overview" => "https://vimeo.com/289289349",
				"SmartTiers™ Technical Overview" => "https://vimeo.com/287921609"
			),
			"related_hl" => array(
				"Undertanding Amazon EBS Storage Classes" => "http://docs.aws.amazon.com/AWSEC2/latest/UserGuide/AmazonEBS.html",
				"Introduction to Microsoft Azure Storage" => "https://docs.microsoft.com/en-us/azure/storage/common/storage-introduction#disk-storage"
			)
		),
		"secondary_tier_cold" => array(
			"id" => 6147,
			"text" => "The cold tier will contain data blocks migrated from the warn tier after the user defined migration policy threshold is reached. The cold tier normally consists of a more archival type of storage device with for data needing to be retained and not accessed frequently. When blocks of data which reside on the tertiary tier is accessed, the read request is automatically addressed and the reference block of data is migrated back to the primary \"hot\" tier. Blocks of data that have been moved back to the primary \"hot\" tier will adhere to the user defined migration policy.
						
						Selecting \"Next\" will take to naming your Tiered Device Name. After naming your device, you will be able to configure the migration policy for each of the tiers.",
			"doc_hl" => array(
				"SmartTiers™ Configuration" => "https://docs.softnas.com/pages/viewpage.action?pageId=18808879",
				"RAM Sizing Guidance for SmartTiers™" => "https://docs.softnas.com/pages/viewpage.action?pageId=30933018"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"SmartTiers™ Overview" => "https://vimeo.com/289289349",
				"SmartTiers™ Technical Overview" => "https://vimeo.com/287921609"
			),
			"related_hl" => array()
		),
		"secondary_tier_archive" => array(
			"id" => 6148,
			"text" => "The archive tier will contain data blocks migrated from the 3rd (cold) tier after the aging policy threshold has been reached.",
			"doc_hl" => array(
				"SmartTiers™ Configuration" => "https://docs.softnas.com/pages/viewpage.action?pageId=18808879",
				"RAM Sizing Guidance for SmartTiers™" => "https://docs.softnas.com/pages/viewpage.action?pageId=30933018"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"SmartTiers™ Overview" => "https://vimeo.com/289289349",
				"SmartTiers™ Technical Overview" => "https://vimeo.com/287921609"
			),
			"related_hl" => array()
		),
		"tiered_storage_migration_policy" => array(
			"id" => 6785,
			"text" => "The current migration policy can be customized for each of the Tiered LUNs contained in a SmartTiers™ hierarchy. The default value for the Maximum block age is 86,400 seconds (24 hours). The Maximum block age is the value used to determine when accessed blocks of data are moved from high performance to more value appropriate storage devices. The Reverse migration grace period default is 43,200 seconds (12 hours). This is the value for when blocks of data residing on more economical storage devices are moved back to the high performance ones after being accessed by a read inquiry.
						
						Time Conversion Table
						3,600 = 1 hour
						43,200 = 12 hours
						86,400 = 24 hours (1 day)
						432,000 = 120 hours (5 days)
						604,800 = 168 hours (7 days)
						2,592,000 = 720 hours (30 days)
						
						You have the ability to modify the values for each of the tiers within the SmartTiers™ hierarchy by going to the Disk Devices panel and using the \"Configure\" wizard on the /dev/sdtiera device.",
			"doc_hl" => array(
				"SmartTiers™ Configuration" => "https://docs.softnas.com/display/SD/FlexTier+Configuration",
				"RAM Sizing Guidance for SmartTiers™" => "https://docs.softnas.com/pages/viewpage.action?pageId=30933018"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"SmartTiers™ Overview" => "https://vimeo.com/289289349",
				"Managing SmartTiers™ Migration Policies" => "https://vimeo.com/351022988"
			),
			"related_hl" => array()
		),
		"pools_tiered_storage_device_options" => array(
			"id" => 14544,
			"text" => "The Migration Interval is the frequency the SmartTiers™ will be scanned to determine if blocks of data need to be migrated between the tiers within the specific Tiered Device being displayed.
						
						You can set a \"high water mark\" for the hot tier by providing a percentage in \"Hot tier storage threshold (%)\".
						
						Alternate block age (seconds) is used in when the \"Hot tier storage threshold\" has been met. This value will override the Maximum Block Age value specified.
						
						Migration schedule enables you to schedule the daily time for when migration scans may occur. Scan frequency uses the specified Migration interval value. This is based on the timezone configured for the instance. This is based on a 24 hour format (hh:mm)",
			"doc_hl" => array(
				"SmartTiers™ Configuration" => "https://docs.softnas.com/pages/viewpage.action?pageId=18808879",
				"RAM Sizing Guidance for SmartTiers™" => "https://docs.softnas.com/pages/viewpage.action?pageId=30933018"
			),
			"kb_hl" => array(
				"SmartTiers™ Calculator" => "https://www.softnas.com/wp/calculator/"
			),
			"video_hl" => array(
				"SmartTiers™ Overview" => "https://vimeo.com/289289349",
				"Increasing Capacity of a SmartTiers™" => "https://vimeo.com/350873025"
			),
			"related_hl" => array()
		),
		"tier_pool_name" => array(
			"id" => 14067,
			"text" => "Enter a name for the SoftNAS® SmartTiers™ pool you have just created. This name will be used in Volumes and LUNS when creating a Volume that can be shared via NFS, CIFS Shares, AFP or iSCSI. This name will be displayed when selecting a Storage Pool for a volume being created.",
			"doc_hl" => array(
				"SmartTiers™ Configuration" => "https://docs.softnas.com/pages/viewpage.action?pageId=18808879"
			),
			"kb_hl" => array(),
			"video_hl" => array(),
			"related_hl" => array()
		)
	);

	public $help_schedules = array();
	public $help_snapreplicate = array(
		"snapreplicate_main_panel" => array(
			"id" => 17550,
			"text" => "SnapReplicate/SNAP HA provides a similar experience across all of the supported platforms. After it has been set up, it is important to follow specific processes in order to perform:
						
						<ul>
						<li>Manual Takeover and Giveback</li>
						<li>Maintenance Mode and Upgrading SNAP HA Pairing</li>
						<li>How to recover from a High Availability Failure</li>
						</ul>
					<b>Failure to perform these procedures can result in data loss.</b>",
			"doc_hl" => array(
				"Manual Takeover and Giveback" => "https://docs.softnas.com/display/SD/Manual+Takeover+and+Giveback",
				"Maintenance Mode and Upgrading SNAP HA Pairing" => "https://docs.softnas.com/pages/viewpage.action?pageId=48988296"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"How to Recover from a High Availability Failure" => "https://vimeo.com/397242823"
			),
			"related_hl" => array(
				"Automatic Failover" => "https://docs.softnas.com/display/SD/Automatic+Failover"
			)
		),
		"add_replication" => array(
			"id" => 5341,
			"text" => "SoftNAS now supports the set up of highly available VPCs with private subnets using virtual IPs. Elastic IP setup is still supported for legacy purposes. However, Virtual IP setup, more secure because it does not require a public facing IP, is our recommended best practice.",
			"doc_hl" => array(
				"Amazon Web Services VPC: Virtual IP Setup" => "https://docs.softnas.com/display/SD/Amazon+Web+Services+VPC_+Virtual+IP+Setup",
				"Premise-based HA Architecture" => "https://docs.softnas.com/display/SD/Premise-based+HA+Architecture",
				"AWS VPC Architecture: Virtual IP" => "https://docs.softnas.com/display/SD/AWS+VPC+Architecture%3A+Virtual+IP"
			),
			"kb_hl" => array(
				"What can I use for a Virtual IP (VIP) with SoftNAS Private HA" => "https://www.softnas.com/helpdesk/index.php?/Knowledgebase/Article/View/59/1/softnas-kb-what-can-i-use-for-a-virtual-ip-vip-with-softnas-private-ha"
			),
			"video_hl" => array(
				"How To: Configuring High Availability and Availability Sets for SoftNAS Cloud on Microsoft Azure" => "https://vimeo.com/290743572/db97e1bf42",
				"SoftNAS Cloud on AWS - Configuring HA for Private VPCs" => "https://vimeo.com/290742145/7a5e75e999"
			),
			"related_hl" => array()
		)
	);
	public $help_ultrafast = array();
	public $help_update = array(
		"apply_update" => array(
			"id" => 5250,
				"text" => "Here you can select the desired update version for your instance. In order to be able to receive and apply updates in a configuration behind a corporate firewall, communication through the firewall must first be set-up. For more information about this process, see the knowledgebase article and listed QuickHelp video.",
				"doc_hl" => array(),
				"kb_hl" => array(
					"Enabling Software Updates through a Firewall" => "https://docs.softnas.com/display/KBS/Upgrading+SoftNAS+through+a+Firewall"
				),
				"video_hl" => array(
					"How to: Overview of Firewall and Security Group Configuration for Fuusion" => "https://vimeo.com/290753353/802e1a352a"
				),
				"related_hl" => array()
			)
	);

	public $help_volumes = array(
		"create_volume" => array(
			"id" => 5049,
			"text" => "Provide the name of the volume to be created, and assign your volume to an existing pool. The Volume Name entered will become part of the filesystem naming being exported and/or shared. You can type the pool name or select it by clicking the Storage Pool button.
						
						Once the pool that the volume will reside in is selected, you can decide how you want to share your volume. You can share your volume as a CIFS, NFS, or AFP share, or as an iSCSI LUN. The same volume can be shared in multiple formats.
						
						Snapshot scheduling can be established during this step as well, by selecting the Snapshots tab.",
			"doc_hl" => array(
				"Create & Configure Volumes" => "https://www.softnas.com/docs/softnas/v3/html/create___configure_volumes.html",
				"Creating a New Volume" => "https://docs.softnas.com/display/SD/Managing+Volumes+and+LUNs#ManagingVolumesandLUNs-CreatingaNewVolume"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Creating Volumes and LUNs" => "https://vimeo.com/292004950/529c97525f"
			),
			"related_hl" => array()
		),
		"create_volume_snapshot" => array(
			"id" => 5050,
			"text" => "Here you can select established snapshot frequency schedules, enable the volume to be governed by a selected snapshot schedule, and set up a new schedule by clicking the Schedules button. You can also determine the number of snapshots you wish to keep.",
			"doc_hl" => array(
				"Snapshots in StorageCenter" => "https://docs.softnas.com/display/SD/Snapshots+in+StorageCenter",
				"Creating a New Volume" => "https://docs.softnas.com/display/SD/Managing+Volumes+and+LUNs#ManagingVolumesandLUNs-CreatingaNewVolume"
			),
			"kb_hl" => array(
				"Creating and Managing Snapshots in SoftNAS" => "https://softnas.com/helpdesk/index.php?/Knowledgebase/Article/View/161/0/softnas-kb-creating-and-managing-snapshots-in-softnas"
			),
			"video_hl" => array(
				"How to Create Instant Writable SnapClones" => "https://vimeo.com/290752022/51d8f76bbd"
			),
			"related_hl" => array()
		),
		"create_volume_filesystem" => array(
			"id" => 5104,
			"text" => "You have the ability to share your volume using any or all of the file sharing protocols (NFS, CIFS/SMB and AFP) simultaneously, in order to make your volume widely accessible.
						
						Unlike Linux, many operating systems and applications are case sensitive. Selecting \"Case insensitive filesystem\" allows you to name volumes using a mixture of upper and lowercase characters in SoftNAS Cloud without impacting the target environment.
						
						A case-sensitive program that expects you to enter all commands in uppercase will not respond correctly if you enter one or more characters in lowercase. It will treat the command RUN differently from run. Programs that do not distinguish between uppercase and lowercase are said to be case-insensitive.",
			"doc_hl" => array(
				"Create & Configure Volumes" => "https://docs.softnas.com/pages/viewpage.action?pageId=65712"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Creating Volume Types" => "https://vimeo.com/290751226/b1d1fdf3ef"
			),
			"related_hl" => array(
				"Case Sensitivity" => "https://en.wikipedia.org/wiki/Case_sensitivity"
			)
		),
		"create_volume_iscsi_lun" => array(
			"id" => 5051,
			"text" => "When creating an iSCSI LUN, it is possible to assign the LUN Targets prior to creation. Simply click the share as iSCSI LUN checkbox.

						iSCSI LUNs are automatically thick provisioned and require the user to enter the appropriate size.",
			"doc_hl" => array(
				"iSCSI LUNs and Targets" => "https://docs.softnas.com/display/SD/iSCSI+LUNs+and+Targets",
				"Creating a New Volume" => "https://docs.softnas.com/display/SD/Managing+Volumes+and+LUNs#ManagingVolumesandLUNs-CreatingaNewVolume"
			),
			"kb_hl" => array(),
			"video_hl" => array(),
			"related_hl" => array()
		),
		"volume_size_options" => array(
			"id" => 14013,
			"text" => "When creating Volumes, the Total Space available will reflect what is able to be used. Some of the capacity specified when creating the disk devices is used for pool and volume overhead. This overhead is used for RAID and other administrative needs.
						
						Example:
						- Raid 1/10 will be used, meaning 2 devices will be allocated (thus 2X the storage)
						- Overhead will use up some of the storage, so you will not get 100% volume capacity equal to what \"Size\" is set to
						
						After setting up a 500GB, the actual usable space is ~497GB.
						- Devices: 2 Disk Devices of Total Size 556GB (2X devices to satisfy the RAID 1/10 mirror)
						- Pool: Total Space of 496.9GB
						- Volume: Total Space 497GB",
			"doc_hl" => array(
				"Create & Configure Volumes" => "https://docs.softnas.com/pages/viewpage.action?pageId=65712",
				"SmartTiers™ Configuration" => "https://docs.softnas.com/pages/viewpage.action?pageId=18808879",
				"RAM Sizing Guidance for SmartTiers™" => "https://docs.softnas.com/pages/viewpage.action?pageId=30933018"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Create Volumes and LUNs" => "https://vimeo.com/292004950/529c97525f",
				"SmartTiers™ Overview" => "https://vimeo.com/289289349"
			),
			"related_hl" => array(
				"SmartTiers™ Technical Overview" => "https://vimeo.com/287921609"
			)
		),
		"create_volume_sync_mode" => array(
			"id" => 5052,
			"text" => "Sync (Synchronous file system transaction) Modes:
						
						standard: This is the default option. Synchronous transactions are written to the intent log and all drives written are flushed to ensure the data is stable (not cached by device controllers).
						
						always: For the ultra-cautious, every file system transaction is written and flushed to stable storage by a system call return. This obviously has a big performance penalty.
						
						disabled: Synchronous requests are disabled. File system transactions only commit to stable storage on the next DMU transaction group commit which can be many seconds. This option gives the highest performance but increases potential data loss.",
			"doc_hl" => array(
				"Create & Configure Volumes" => "https://docs.softnas.com/pages/viewpage.action?pageId=65712",
				"Managing Volumes and LUNs" => "https://docs.softnas.com/display/SD/Managing+Volumes+and+LUNs"
			),
			"kb_hl" => array(),
			"video_hl" => array(),
			"related_hl" => array()
		),
		"edit_volume" => array(
			"id" => 5053,
			"text" => "You cannot edit the name and type of the volume. However, you can apply compression and deduplication, as well as change the size.
						
						When Thin Provision is selected the Volume Size specifies how much space is to be pre-allocated to the volume. Space is determined by entering a Volume Size amount as a floating point value, along with choosing the Size Unit.",
			"doc_hl" => array(
				"Managing Volumes and LUNs" => "https://docs.softnas.com/display/SD/Managing+Volumes+and+LUNs",
				"Editing a Volume" => "https://docs.softnas.com/display/SD/Managing+Volumes+and+LUNs#ManagingVolumesandLUNs-EditingaVolume"
			),
			"kb_hl" => array(),
			"video_hl" => array(),
			"related_hl" => array()
		),
		"edit_volume_snapshots" => array(
			"id" => 5054,
			"text" => "The characteristics of a specific snapshot of a volume can be altered to a different schedule, retention policy and even be turned on or off from this wizard.",
			"doc_hl" => array(
				"Snapshots in StorageCenter" => "https://docs.softnas.com/display/SD/Snapshots+in+StorageCenter",
				"Managing Snapshots" => "https://docs.softnas.com/display/SD/Managing+Snapshots",
				"Creating a SnapClone™ from a Snapshot" => "https://docs.softnas.com/display/SD/Managing+Snapshots#ManagingSnapshots-Creatingsnapclonefromsnapshot"
			),
			"kb_hl" => array(),
			"video_hl" => array(
				"Creating a SnapClone™ from a Snapshot" => "https://www.youtube.com/embed/_ylvJdwKjHQ?list=PL-BYjDczh7nwSAVJjIIPfu9Fxeozp_Mm4"
			),
			"related_hl" => array()
		),
		"edit_volume_syncmode" => array(
			"id" => 5105,
			"text" => "Sync (Synchronous file system transaction) Modes:
						
						standard: This is the default option. Synchronous transactions are written to the intent log and all drives written are flushed to ensure the data is stable (not cached by device controllers).
						
						always: For the ultra-cautious, every file system transaction is written and flushed to stable storage by a system call return. This obviously has a big performance penalty.
						
						disabled: Synchronous requests are disabled. File system transactions only commit to stable storage on the next DMU transaction group commit which can be many seconds. This option gives the highest performance but increases potential data loss.",
			"doc_hl" => array(
				"Create & Configure Volumes" => "https://docs.softnas.com/pages/viewpage.action?pageId=65712",
				"Managing Volumes and LUNs" => "https://docs.softnas.com/display/SD/Managing+Volumes+and+LUNs"
			),
			"kb_hl" => array(),
			"video_hl" => array(),
			"related_hl" => array()
		),
		"adwiz" => array(
			"id" => 5366, // (old 6969)
			"text" => "Integration of SoftNAS Cloud into Active Directory enables domain users to more securely share files and data in a corporate environment.
						
						Authentication is managed by Active Directory (AD) via Kerberos.
						
						SoftNAS uses Sernet Samba to accomplish CIFS and Active Directory integration. SoftNAS can also sync these permissions and File Ownerships to NFSv4 Clients running Linux based Operating systems.",
			"doc_hl" => array(
				"Active Directory Configuration" => "https://docs.softnas.com/display/SD/Active+Directory+Configuration"
			),
			"kb_hl" => array(
				"How To Configure Existing AD (for 2008 and up) with NIS Server to SoftNAS Samba" => "https://docs.softnas.com/display/KBS/%5BSoftNAS+KB%5D%3A+How+To+Configure+Existing+AD+%28for+2008+and+up%29+with+NIS+Server+to+SoftNAS+Samba"
			),
			"video_hl" => array(
				"Configuring Azure Active Directory" => "https://vimeo.com/286756897/4312e91b4d",
				"Joining Azure Active Directory" => "https://vimeo.com/290766967/7c2e35eb77"
			),
			"related_hl" => array()
		),
		"adwiz_user" => array(
			"id" => 6970,
			"text" => "Active Directory Domain user which is capable to join workstations to Active Directory",
			"doc_hl" => array(
			    "How to set up Minimum Permissions required to join SoftNAS to AD" => "https://docs.softnas.com/display/SD/How+to+set+up+Minimum+Permissions+required+to+join+SoftNAS+to+AD",
                "Active Directory" => "https://docs.softnas.com/display/SD/Active+Directory"
            ),
			"kb_hl" => array(),
			"video_hl" => array(),
			"related_hl" => array()
		),
		"adwiz_dcip" => array(
			"id" => 6971,
			"text" => "test",
			"doc_hl" => array(),
			"kb_hl" => array(),
			"video_hl" => array(),
			"related_hl" => array()
		),
		"adwiz_domain" => array(
			"id" => 6972,
			"text" => "Fully Qualified Domain Name of the Active Directory Domain",
			"doc_hl" => array(
                "Active Directory" => "https://docs.softnas.com/display/SD/Active+Directory"
            ),
			"kb_hl" => array(),
			"video_hl" => array(),
			"related_hl" => array()
		)
	);
	public $help_welcome = array();
	
}

class QuickHelpAWS extends QuickHelp {
	
}
class QuickHelpAzure extends QuickHelp {
	function __construct() {
		$this->help_diskdevices["add_disk_type"] = array(
			"id" => 5012,
			"text" => "Choose the type of storage for the disk being added.
						
						- Select Cloud Disk Extender for Object Storage.
						
						- Select Microsoft Cloud Disk Extender for Block Storage.",
			"doc_hl" => array(
				"Adding Block Storage via the SoftNAS UI" => "https://docs.softnas.com/display/SD/Adding+Block+Storage+via+the+SoftNAS+UI",
				"Adding Object Storage via the SoftNAS UI" => "https://docs.softnas.com/display/SD/Adding+Object+Storage+via+the+SoftNAS+UI"
			),
			"kb_hl" => array(
				"SoftNAS on Azure Sizing Guide" => "https://www.softnas.com/helpdesk/index.php?/Knowledgebase/Article/View/193/0/softnas-kb-softnas-on-azure-sizing-guide"
			),
			"video_hl" => array(
				"Creating Azure Storage Devices" => "https://vimeo.com/291738221/651576b8e0",
				"How To: Understanding and Adding Blob Storage for SoftNAS Cloud on Azure" => "https://vimeo.com/290756017/8db523e2fb"
			),
			"related_hl" => array(
				"How To: Understanding and Adding Block Storage for SoftNAS Cloud on Azure" => "https://vimeo.com/290756648/3797c5f1dd",
				"Introducing Azure Cool Blob Storage" => "https://azure.microsoft.com/en-us/blog/introducing-azure-cool-storage/",
				"How To: Using Hot and Cool Blob Storage and Multiple Blob Storage Accounts for SoftNAS Cloud" => "https://vimeo.com/290760104/93e662a448",
				"Introduction to Microsoft Azure Storage" => "https://docs.microsoft.com/en-us/azure/storage/storage-introduction"
			)
		);
		$this->help_diskdevices["combo_add_disk_extender"] = array(
			"id" => 5013,
			"text" => "Select the desired type of Object Storage.
				
				- For SoftNAS Cloud (R) instances running in AWS, select Amazon Web Services S3.
				- For SoftNAS Cloud (R) instances running in Azure, select Azure Blob.
				- For SoftNAS Cloud (R) instances running on other platforms, including VMWare, select the correct type.
				- If you do not see your specific Object Storage type, you can use “Self Configured”.",
			"doc_hl" => array(
				"Adding Storage in Microsoft Azure" => "https://docs.softnas.com/display/SD/Adding+Storage%3A+Microsoft+Azure",
				"Adding Storage: Amazon Web Services (AWS)" => "https://docs.softnas.com/pages/viewpage.action?pageId=65787",
				"Adding Storage: VMware vSphere" => "https://docs.softnas.com/display/SD/Adding+Storage%3A+VMware+vSphere"
			),
			"kb_hl" => array(
				"SoftNAS on Azure Sizing Guide" => "https://www.softnas.com/helpdesk/index.php?/Knowledgebase/Article/View/193/0/softnas-kb-softnas-on-azure-sizing-guide"
			),
			"video_hl" => array(),
			"related_hl" => array(
				"Introduction to Microsoft Azure Storage" => "https://docs.microsoft.com/en-us/azure/storage/"
			)
		);
	}
}
class QuickHelpVMware extends QuickHelp {
	
}

function get_quick_help($applet, $quick_help_id, $asTag = false) {
	$platform = get_system_platform();
	if($platform === 'amazon') {
		$help = new QuickHelpAWS();
	}
	if ($platform === 'azure') {
		$help = new QuickHelpAzure();
	}
	if ($platform === 'VM') {
		$help = new QuickHelpVMware();
	}
	
	$help_data = $help->{"help_$applet"};
	$help_data = $help_data[$quick_help_id];
	
	$tracking_file = __DIR__.'/../logs/quickhelp_journey.json'; // #5718 - add quickhelp journey to json tracker
	$tracking_data = array(
		'timestamp' => date('Y-m-d H:i:s'),
		'unix_timestamp' => time(),
		'applet' => $applet,
		'quick_help_id' => $quick_help_id,
		'ticket_id' => $help_data['id']
	);
	$tracker = array();
	
	if(file_exists($tracking_file)) {
		$tracker = json_decode(file_get_contents($tracking_file), true);
		
		if(!is_array($tracker)) {
			$tracker = array();
		}
	}
	
	array_push($tracker, $tracking_data);
	file_put_contents($tracking_file, json_encode($tracker, true));
	
	if(!$asTag) {
		return $help_data;
	}
	
	$help_data_json = json_encode($help_data);
}

function get_quick_help_data($applet, $quick_help_id) {
	get_quick_help($applet, $quick_help_id, true);
}

function get_quick_help_array($applet, $quick_help_id) {
	return get_quick_help($applet, $quick_help_id);
}

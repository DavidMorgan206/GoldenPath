<?php

use Stradella\GPaths\AffiliateNode;
use Stradella\GPaths\Flow;
use Stradella\GPaths\DataStore;
use Stradella\GPaths\LandingNode;
use Stradella\GPaths\ListNode;
use Stradella\GPaths\ManualNode;

/**
 * @throws Exception
 */
function create_dog_dues_sample_site()
{
    $dataStore = new DataStore();

    $root = new LandingNode($dataStore);
    $root->childrenAreExclusive = false;
    $root->skippable = false;
    $root->bodyPaneHtml='Thinking about getting a dog and wondering about the cost? It\'s a big decision, so good job thinking ahead!  By clicking through a few simple questions we can help you come up with a budget that works for you and your new pal (no registration required).';
//    $root->linkPaneHtml='[caption id="attachment_83" align="alignnone" width="600"]<img src="http://localhost/wp-content/uploads/2020/01/IMG_7490.jpg" alt=""/> Trilly, our mascot[/caption]';
    $root->linkPaneHtml='<img src="http://localhost/wp-content/uploads/2020/01/IMG_7490.jpg" alt=""/>';
    $root->heading='How much is that dog in the window?';
    $root->title='Intro';
    $root->createInDataStore();

    $flow = new Flow($dataStore);
    $flow->title = 'Dog Dues';
    $flow->id = $root->id;
    $flow->summaryTitle = ('Whoa, That\'s a Lotta Bones!');
    $flow->summaryBody = ('<p style="text-align:right;"><img src="http://localhost/wp-content/uploads/2020/07/IMG_8815.jpg" width="100%" alt=""" /><i>Priceless</i></p>' .
                  ' <p>Forgive the pun, we\'re sure you\'ve set a very reasonable budget.  Click on any item to go back and change an answer or "Start Over" completely down below.</p>');
    $flow->createInDataStore();

    $adoptionCost = new ManualNode($dataStore);
    $adoptionCost->skippable = true;
    $adoptionCost->title = 'Adoption Costs';
    $adoptionCost->bodyPaneHtml = 'First thing\'s first, how were you planning to adopt your new pup? To adopt a rescue, plan to pay $300-400 (includes spay/neuter and first round of shots).  A puppy from a breeder will cost $1500-3000 (depending on bread and pedigree).';
    $adoptionCost->linkPaneHtml = '<img src="http://localhost/wp-content/uploads/2020/01/IMG_20190830_080729-1-768x1024.jpg" alt="" width="768" height="1024" class="size-large wp-image-95" />';
    $adoptionCost->setParent($root, 1);
    $adoptionCost->allowCustomPrice = true;
    $adoptionCost->defaultPrice = 450.00;
    $adoptionCost->createInDataStore();

    $medicalCost = new ManualNode($dataStore);
    $medicalCost->skippable = true;
    $medicalCost->title = 'Medical Costs';
    $medicalCost->heading = 'First Year Medical Costs';
    $medicalCost->allowCustomPrice = true;
    $medicalCost->defaultPrice = 500.00;
    $medicalCost->setParent($root, 2);
    $medicalCost->bodyPaneHtml = 'Beyond the $500 that\'s typically needed during the initial adoption, most pet owners spend about $500 per year on vetranary costs.  Pet insurance is a good option for some, costs can be similar';
    $medicalCost->linkPaneHtml = '<img src="http://localhost/wp-content/uploads/2020/01/IMG_20190830_090938-1-1024x768.jpg" alt="" width="1024" height="768" class="size-large wp-image-97" />';
    $medicalCost->createInDataStore();
    
    $fencing = new LandingNode($dataStore);
    $fencing->skippable = true;
    $fencing->title = 'Puppy Proofing';
    $fencing->heading = 'Puppy Proofing Your House';
    $fencing->bodyPaneHtml = '<p>A well trained adult dog might not require much prep, but you need to plan on your puppy being an absolute terror the first 6 months.</p>';
    $fencing->linkPaneHtml = '<img src="http://localhost/wp-content/uploads/2020/01/IMG_8800-2-1024x683.jpg" alt="" height="250" " />';
    $fencing->setParent($root, 3);
    $fencing->createInDataStore();

    $fencingOptions = new ListNode($dataStore);
    $fencingOptions->title = 'Fencing Options';
    $fencingOptions->bodyPaneHtml = '<p>While not always possible, it\'s great to have an area where your pup can run around off leash. Temporary or permanent? Pretty or cheap? There are lots of factors to consider when planning for fencing. Be sure to consider your dog\'s full grown size!</p>';
    $fencingOptions->setParent($fencing, 1);
    $fencingOptions->createInDataStore();

    $fencingOptionPortable = new AffiliateNode($dataStore);
    $fencingOptionPortable->title = 'Portable Fencing';
    $fencingOptionPortable->imagePaneHtml = '<img src="http://localhost/wp-content/uploads/2020/06/IMG_20180517_114008_01-e1593106819179.jpg" alt="" width="1024" height="683" class="alignnone size-large wp-image-98" />';
    $fencingOptionPortable->bodyPaneHtml = 'Portable fencing is a great option for smaller breads and very young pups. We also used this to train Trilly to stay by our feet when we\'re at the desk.';
    $fencingOptionPortable->linkPaneHtml = '<iframe style="width:120px;height:240px;" marginwidth="0" marginheight="0" scrolling="no" frameborder="0" src="//ws-na.amazon-adsystem.com/widgets/q?ServiceVersion=20070822&OneJS=1&Operation=GetAdHtml&MarketPlace=US&source=ss&ref=as_ss_li_til&ad_type=product_link&tracking_id=theh0bc-20&language=en_US&marketplace=amazon&region=US&placement=B0758FX7MT&asins=B0758FX7MT&linkId=6cc1ad58f7faa6db87a80c243b1f0f54&show_border=false&link_opens_in_new_window=true"></iframe>';
    $fencingOptionPortable->setParent($fencingOptions, 10);
    $fencingOptionPortable->createInDataStore();

    $fencingOptionHog = new ManualNode($dataStore);
    $fencingOptionHog->title = 'Hog Fencing';
    $fencingOptionHog->bodyPaneHtml = 'What hog fencing lacks in looks it makes up for by being quick and cheap. Plan to pay x per foot.';
    $fencingOptionHog->linkPaneHtml = '<img src="http://localhost/wp-content/uploads/2020/06/siora-photography-5QkqcttIwOM-unsplash.jpg" alt="" width="1024" height="683" class="alignnone size-large wp-image-98" />';
    $fencingOptionHog->allowCustomPrice = true;
    $fencingOptionHog->defaultPrice = 0.00;
    $fencingOptionHog->setParent($fencingOptions, 2);
    $fencingOptionHog->createInDataStore();

    $fencingOptionChainlink = new ManualNode($dataStore);
    $fencingOptionChainlink->title = 'Chainlink Fencing';
    $fencingOptionChainlink->bodyPaneHtml = 'Chainlink is a sturdier, more permanent upgrade to hog fencing.  Debatably prettier.  Plan to pay x per foot.';
    $fencingOptionChainlink->linkPaneHtml = '<img src="http://localhost/wp-content/uploads/2020/06/isaac-jarnagin-bDSppVMddgc-unsplash.jpg" alt="" width="1024" height="683" class="alignnone size-large wp-image-98" />';
    $fencingOptionChainlink->allowCustomPrice = true;
    $fencingOptionChainlink->defaultPrice = 0.00;
    $fencingOptionChainlink->setParent($fencingOptions, 2);
    $fencingOptionChainlink->createInDataStore();

    $fencingOptionWood = new ManualNode($dataStore);
    $fencingOptionWood->title = 'Wood Fencing';
    $fencingOptionWood->bodyPaneHtml = 'Pretty looking, pretty expensive, wood is a timeless choice for your pup.  It can also be an crucial privacy screen to discourage fido from barking at passing people and cars. Plan to pay x per foot.';
    $fencingOptionWood->linkPaneHtml = '<img src="http://localhost/wp-content/uploads/2020/06/yongha-bae-3UP52XwlRyw-unsplash1.jpg" alt="" width="1024" height="683" class="alignnone size-large wp-image-98" />';
    $fencingOptionWood->allowCustomPrice = true;
    $fencingOptionWood->defaultPrice = 0.00;
    $fencingOptionWood->setParent($fencingOptions, 2);
    $fencingOptionWood->createInDataStore();

    $fencingOptionDigGuard = new ManualNode($dataStore);
    $fencingOptionDigGuard->title = 'Dig Guards';
    $fencingOptionDigGuard->bodyPaneHtml = 'Essential for some breads, dig guards keep your pup from being able to tunnel under your nice new fence.  We swear by this method LINK for all but the most persistant diggers.';
    $fencingOptionDigGuard->linkPaneHtml = '<img src="http://localhost/wp-content/uploads/2020/06/dane-deaner-wnkE42AFNZg-unsplash.jpg"  alt="" width="1024" height="683" class="alignnone size-large wp-image-98" />';
    $fencingOptionDigGuard->allowCustomPrice = true;
    $fencingOptionDigGuard->defaultPrice = 0.00;
    $fencingOptionDigGuard->setParent($fencingOptions, 2);
    $fencingOptionDigGuard->createInDataStore();

    $fencingOptionGate = new ManualNode($dataStore);
    $fencingOptionGate->title = 'Gates';
    $fencingOptionGate->bodyPaneHtml = 'Don\'t forget to budget for gates.  They can be surprisingly expensive.  A simple 4\'x2.5\' chainlink gate runs $80.';
    $fencingOptionGate->linkPaneHtml = '<img src="http://localhost/wp-content/uploads/2020/06/sebastian-coman-travel-LtZCjTtLEP8-unsplash-3.jpg" alt="" width="1024" height="683" class="alignnone size-large wp-image-98" />';
    $fencingOptionGate->allowCustomPrice = true;
    $fencingOptionGate->defaultPrice = 0.00;
    $fencingOptionGate->setParent($fencingOptions, 2);
    $fencingOptionGate->createInDataStore();

    $collar = new AffiliateNode($dataStore);
    $collar->skippable = true;
    $collar->heading= "Collar? I barely know her!";
    $collar->title = "Collar";
    $collar->bodyPaneHtml = 'Most dogs are going to need a couple sizes of collars as they grow. We really liked these embroidered collars for our dogs since the dangly tags would always go missing.<br> Talk to your vet about chipping as a great backup in case fido gets lost.';
    $collar->linkPaneHtml = '<iframe style="width:120px;height:240px;" marginwidth="0" marginheight="0" scrolling="no" frameborder="0" src="//ws-na.amazon-adsystem.com/widgets/q?ServiceVersion=20070822&OneJS=1&Operation=GetAdHtml&MarketPlace=US&source=ss&ref=as_ss_li_til&ad_type=product_link&tracking_id=theh0bc-20&language=en_US&marketplace=amazon&region=US&placement=B01BF4K7VQ&asins=B01BF4K7VQ&linkId=86a02f32c14ac2103a4e92d448d35428&show_border=true&link_opens_in_new_window=true"></iframe>';
    $collar->imagePaneHtml = '<img src = "http://localhost/wp-content/uploads/2020/07/IMG_7509.jpg"/>';
    $collar->setParent($root, 4);
    $collar->createInDataStore();

    $dogTraining = new ListNode($dataStore);
    $dogTraining->title = 'Teach your new dog new Tricks!';
    $dogTraining->bodyPaneHtml = '<table><tr><td><img src="http://localhost/wp-content/uploads/2020/07/IMG_7495.jpg" alt="" width="1024" height="768" class="size-large wp-image-97" /></td><td>Even if you\'re not trying to train the next Lassie, there are some important things you need to cover with your puppy throughout that first year. If you\'re adopting a rescue you may have some of this work done for you, but others that need special attention.  In both cases your rescue/breeder will be able to give you the best recommendations.</td></tr></table>';
    $dogTraining->setParent($root, 5);
    $dogTraining->createInDataStore();

    $dogTrainingBook = new AffiliateNode($dataStore);
    $dogTrainingBook->title = 'Youtube\'s favorite dog trainer';
    $dogTrainingBook->bodyPaneHtml = 'All you need to know for training a house dog / companion animal.';
    $dogTrainingBook->linkPaneHtml = '<iframe style="width:120px;height:240px;" marginwidth="0" marginheight="0" scrolling="no" frameborder="0" src="//ws-na.amazon-adsystem.com/widgets/q?ServiceVersion=20070822&OneJS=1&Operation=GetAdHtml&MarketPlace=US&source=ss&ref=as_ss_li_til&ad_type=product_link&tracking_id=theh0bc-20&language=en_US&marketplace=amazon&region=US&placement=1607748916&asins=1607748916&linkId=20363de24c437a3c19b24be5e957be8f&show_border=true&link_opens_in_new_window=true"></iframe>';
    $dogTrainingBook->setParent($dogTraining, 10);
    $dogTrainingBook->createInDataStore();

    $dogTrainingClasses = new ManualNode($dataStore);
    $dogTrainingClasses->title = 'Puppy Classes';
    $dogTrainingClasses->bodyPaneHtml = 'This may seem kind of silly, but at worst it\'s a great way to start socializing your dog with other dogs in a controlled setting.';
    $dogTrainingClasses->linkPaneHtml = '<img src="http://localhost/wp-content/uploads/2020/07/IMG_20180726_180929.jpg" alt="" width="1024" height="683" class="alignnone size-large wp-image-98" />';
    $dogTrainingClasses->allowCustomPrice = true;
    $dogTrainingClasses->defaultPrice = 120.00;
    $dogTrainingClasses->setParent($dogTraining, 2);
    $dogTrainingClasses->createInDataStore();
/*
    $dogBed = new ManualNode($dataStore);
    $dogBed->skippable = true;
    $dogBed->title = 'Bedding';
    $dogBed->heading = 'Crates and Beds';
    $dogBed->allowCustomPrice = true;
    $dogBed->defaultPrice = 40.00;
    $dogBed->setParent($root, 8);
    $dogBed->bodyPaneHtml = 'Let sleeping dogs lay [somewhere comfortable]. Not just for posh pooches, your dog will appreciate having a place to call their own.';
    $dogBed->linkPaneHtml = '<img src="http://localhost/wp-content/uploads/2020/07/IMG_20191031_131554-2.jpg" alt="" width="1024" height="768" class="size-large wp-image-97" />';
    $dogBed->createInDataStore();
*/
}

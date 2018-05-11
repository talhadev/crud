<html>
<head>
    <title>Items sold summary</title>
    <style>
        table {
            border-collapse: collapse;
            border-bottom: 1px solid grey;
            border-top: 1px solid grey;
            border-left: 2px solid grey;
            border-right: 2px solid grey;
            letter-spacing: 1px;
            font-size: 0.8rem;
        }
        td, th {
            border: 0.5px solid black;
            padding: 5px 7px;
            text-align: left;
            border: 2px solid grey;
            font-family: Arial;
        }
        th {
            background-color: white;
        }
        td {
            text-align: left;
            margin-left: -50px;
        }
        tr:nth-child(even) td {
            background-color: white;
        }
        tr:nth-child(odd) td {
            background-color: white;
        }
        caption {
            padding: 2px;
        }
        tbody {
            font-size: 15px;
            font-style: normal;
            font-family: Arial;
        }
        tfoot {
            font-weight: bold;
        }
        .consigneeBox{
            border: 1px solid black; height: 30px;  border-bottom:2px solid grey; width:149px; margin-top: -55px;margin-left: -20px;
        }
    </style>
</head>
<body>

<table>


<tr>
    <td colspan="10" rowspan="5"><img src="{{URL::asset('call_courier.png')}}" style="margin-top:-5px;" alt=""/><h3 style="float:right; border: 1px solid grey; padding:5px 20px;">CONSIGNEE COPY</h3><br><img src="http://barcodes4.me/barcode/c39/{{$data['courier_data']['ConsignmentNo']}}.png?width=355&height=55" style="margin-left:50px;margin-top:-6px;" ><br><p style="margin-top:-5px; margin-left: 150px;">{{$data['courier_data']['ConsignmentNo']}}</p></td>
    <td rowspan="8" style="border-top:1px solid white;"></td>
    <td colspan="2">Date</td>
    <td colspan="3">{{$data['date']}}</td>
    <td colspan="3">Time</td>
    <td colspan="3">{{$data['time']}}</td>
    {{--<td>7</td>--}}
    {{--<td>8</td>--}}
    {{--<td>9</td>--}}
    {{--<td>10</td>--}}
    {{--<td>11</td>--}}
    {{--<td>12</td>--}}
    {{--<td>13</td>--}}
    {{--<td>14</td>--}}
    {{--<td>15</td>--}}
    {{--<td>16</td>--}}
    {{--<td>17</td>--}}
    {{--<td>18</td>--}}
    {{--<td>19</td>--}}
    {{--<td>20</td>--}}

</tr>
    <tr>
        <td colspan="2">Service</td>
        <td colspan="3">{{$data['service']}}</td>
        <td colspan="3">Weight</td>
        <td colspan="3">{{$data['order_data']['weight']}}</td>
        {{--<td>25</td>--}}
        {{--<td>26</td>--}}
        {{--<td>27</td>--}}
        {{--<td>28</td>--}}
        {{--<td>29</td>--}}
        {{--<td>30</td>--}}
        {{--<td>31</td>--}}
        {{--<td>32</td>--}}
        {{--<td>33</td>--}}
        {{--<td>34</td>--}}
        {{--<td>35</td>--}}
        {{--<td>36</td>--}}
        {{--<td>37</td>--}}
        {{--<td>38</td>--}}
        {{--<td>39</td>--}}
        {{--<td>40</td>--}}

    </tr>
    <tr>
        <td colspan="2">Fragile</td>
        <td colspan="3">No</td>
        <td colspan="3">Pieces</td>
        <td colspan="3">{{$data['pieces']}}</td>
        {{--<td>45</td>--}}
        {{--<td>46</td>--}}
        {{--<td>47</td>--}}
        {{--<td>48</td>--}}
        {{--<td>49</td>--}}
        {{--<td>50</td>--}}
        {{--<td>51</td>--}}
        {{--<td>52</td>--}}
        {{--<td>53</td>--}}
        {{--<td>54</td>--}}
        {{--<td>55</td>--}}
        {{--<td>56</td>--}}
        {{--<td>57</td>--}}
        {{--<td>58</td>--}}
        {{--<td>59</td>--}}
        {{--<td>60</td>--}}

    </tr>
    <tr>
        <td colspan="2">Origin</td>
        <td colspan="3">{{$data['courier_data']['HomeBranch']}}</td>
        <td colspan="3">Destination</td>
        <td colspan="3">{{$data['courier_data']['DestBranch']}}</td>
        {{--<td>65</td>--}}
        {{--<td>66</td>--}}
        {{--<td>67</td>--}}
        {{--<td>68</td>--}}
        {{--<td>69</td>--}}
        {{--<td>70</td>--}}
        {{--<td>71</td>--}}
        {{--<td>72</td>--}}
        {{--<td>73</td>--}}
        {{--<td>74</td>--}}
        {{--<td>75</td>--}}
        {{--<td>76</td>--}}
        {{--<td>77</td>--}}
        {{--<td>78</td>--}}
        {{--<td>79</td>--}}
        {{--<td>80</td>--}}

    </tr>
    <tr>
        <td colspan="5" style="font-weight:bold; font-size: 13px;">COD Amount PKR {{$data['courier_data']['codAmount']}}</td>
        <td colspan="3" style="font-size:14px;">Decld.Ins.Value</td>
        <td colspan="3">Rs, 0/-</td>
        {{--<td>84</td>--}}
        {{--<td>85</td>--}}
        {{--<td>86</td>--}}
        {{--<td>87</td>--}}
        {{--<td>88</td>--}}
        {{--<td>89</td>--}}
        {{--<td>90</td>--}}
        {{--<td>91</td>--}}
        {{--<td>92</td>--}}
        {{--<td>93</td>--}}
        {{--<td>94</td>--}}
        {{--<td>95</td>--}}
        {{--<td>96</td>--}}
        {{--<td>97</td>--}}
        {{--<td>98</td>--}}
        {{--<td>99</td>--}}
        {{--<td>100</td>--}}

    </tr>
    <tr>
        <td colspan="10" rowspan="2" style="padding: 4px 3px;">Shipper:{{$data['courier_data']['ShipperName']}}-KARACHI<br>
            {{$data['courier_data']['ShipperAddress']}}</td>
        <td colspan="11" rowspan="2" style="padding: 9px 3px; "><p style="margin-top: -10px;">Consignee: {{$data['courier_data']['ConsigneeName']}} {{$data['courier_data']['ContactNo']}}<br>
                {{$data['courier_data']['ConsigneeAddress']}}</p></td>
        {{--<td>103</td>--}}
        {{--<td>104</td>--}}
        {{--<td>105</td>--}}
        {{--<td>106</td>--}}
        {{--<td>107</td>--}}
        {{--<td>108</td>--}}
        {{--<td>109</td>--}}
        {{--<td>200</td>--}}
        {{--<td>201</td>--}}
        {{--<td>202</td>--}}
        {{--<td>203</td>--}}
        {{--<td>204</td>--}}
        {{--<td>205</td>--}}
        {{--<td>206</td>--}}
        {{--<td>207</td>--}}
        {{--<td>208</td>--}}
        {{--<td>209</td>--}}
        {{--<td>300</td>--}}

    </tr>
    <tr>
        {{--<td>301</td>--}}
        {{--<td>302</td>--}}
        {{--<td>303</td>--}}
        {{--<td>304</td>--}}
        {{--<td>305</td>--}}
        {{--<td>306</td>--}}
        {{--<td>307</td>--}}
        {{--<td>308</td>--}}
        {{--<td>309</td>--}}
        {{--<td>400</td>--}}
        {{--<td>401</td>--}}
        {{--<td>402</td>--}}
        {{--<td>403</td>--}}
        {{--<td>404</td>--}}
        {{--<td>405</td>--}}
        {{--<td>406</td>--}}
        {{--<td>407</td>--}}
        {{--<td>408</td>--}}
        {{--<td>409</td>--}}
        {{--<td>410</td>--}}

    </tr>
    <tr>
        <td colspan="5">Customer Ref. #</td>
        <td style="border-right:1px solid white;">None</td>
        <td style="border-right:1px solid white;border-left:1px solid white;">&nbsp</td>
        <td style="border-right:1px solid white;border-left:1px solid white;">&nbsp</td>
        <td style="border-right:1px solid white;border-left:1px solid white;">&nbsp</td>
        <td style="border-right:1px solid white;border-left:1px solid white;">&nbsp</td>
        <td colspan="3" style="font-weight: bold;">Remarks</td>
        <td colspan="8">None</td>
        {{--<td>419</td>--}}
        {{--<td>420</td>--}}
        {{--<td>421</td>--}}
        {{--<td>422</td>--}}
        {{--<td>423</td>--}}
        {{--<td>424</td>--}}
        {{--<td>425</td>--}}
        {{--<td>426</td>--}}
        {{--<td>427</td>--}}


    </tr>




</table>
<div class="container-fluid" style="border:2px solid grey;  height:170px;width:826px;"><p style="font-family:Arial; margin-top:-1px; padding:10px;">
        Product Details: </p>

</div>

<h4 style="font-family:Arial; font-size:15px;margin-top:-1px; ">SPECIAL NOTE for CONSIGNEE: (1) Please don’t accept, if shipment is not intact. (2) Please don’t open the parcel before payment. (3)
    Incase of any defects/complaints in parcel, please contact the shipper/brand. CallCourier is not responsible for any defect.</h4>
<p style="margin-top:-25px;">- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -- - - - - - - - - - - - - - - - - - - </p>

<table>


    <tr>
        <td colspan="10" rowspan="5"><img src="{{URL::asset('call_courier.png')}}" style="margin-top:-5px;" alt=""/><h4 style="float:right; border: 1px solid grey; padding:5px 20px;">Account's Copy</h4><br><img src="http://barcodes4.me/barcode/c39/{{$data['courier_data']['ConsignmentNo']}}.png?width=355&height=55" style="margin-left:50px;margin-top:-6px;" ><br><p style="margin-top:-5px; margin-left: 150px;">{{$data['courier_data']['ConsignmentNo']}}</p></td>
        <td rowspan="8" style="border-top:1px solid white;"></td>
        <td colspan="2">Date</td>
        <td colspan="3">{{$data['date']}}</td>
        <td colspan="3">Time</td>
        <td colspan="3">{{$data['time']}}</td>
        {{--<td>7</td>--}}
        {{--<td>8</td>--}}
        {{--<td>9</td>--}}
        {{--<td>10</td>--}}
        {{--<td>11</td>--}}
        {{--<td>12</td>--}}
        {{--<td>13</td>--}}
        {{--<td>14</td>--}}
        {{--<td>15</td>--}}
        {{--<td>16</td>--}}
        {{--<td>17</td>--}}
        {{--<td>18</td>--}}
        {{--<td>19</td>--}}
        {{--<td>20</td>--}}

    </tr>
    <tr>
        <td colspan="2">Service</td>
        <td colspan="3">{{$data['service']}}</td>
        <td colspan="3">Weight</td>
        <td colspan="3">{{$data['order_data']['weight']}}</td>
        {{--<td>25</td>--}}
        {{--<td>26</td>--}}
        {{--<td>27</td>--}}
        {{--<td>28</td>--}}
        {{--<td>29</td>--}}
        {{--<td>30</td>--}}
        {{--<td>31</td>--}}
        {{--<td>32</td>--}}
        {{--<td>33</td>--}}
        {{--<td>34</td>--}}
        {{--<td>35</td>--}}
        {{--<td>36</td>--}}
        {{--<td>37</td>--}}
        {{--<td>38</td>--}}
        {{--<td>39</td>--}}
        {{--<td>40</td>--}}

    </tr>
    <tr>
        <td colspan="2">Fragile</td>
        <td colspan="3">No</td>
        <td colspan="3">Pieces</td>
        <td colspan="3">{{$data['pieces']}}</td>
        {{--<td>45</td>--}}
        {{--<td>46</td>--}}
        {{--<td>47</td>--}}
        {{--<td>48</td>--}}
        {{--<td>49</td>--}}
        {{--<td>50</td>--}}
        {{--<td>51</td>--}}
        {{--<td>52</td>--}}
        {{--<td>53</td>--}}
        {{--<td>54</td>--}}
        {{--<td>55</td>--}}
        {{--<td>56</td>--}}
        {{--<td>57</td>--}}
        {{--<td>58</td>--}}
        {{--<td>59</td>--}}
        {{--<td>60</td>--}}

    </tr>
    <tr>
        <td colspan="2">Origin</td>
        <td colspan="3">{{$data['courier_data']['HomeBranch']}}</td>
        <td colspan="3">Destination</td>
        <td colspan="3">{{$data['courier_data']['DestBranch']}}</td>
        {{--<td>65</td>--}}
        {{--<td>66</td>--}}
        {{--<td>67</td>--}}
        {{--<td>68</td>--}}
        {{--<td>69</td>--}}
        {{--<td>70</td>--}}
        {{--<td>71</td>--}}
        {{--<td>72</td>--}}
        {{--<td>73</td>--}}
        {{--<td>74</td>--}}
        {{--<td>75</td>--}}
        {{--<td>76</td>--}}
        {{--<td>77</td>--}}
        {{--<td>78</td>--}}
        {{--<td>79</td>--}}
        {{--<td>80</td>--}}

    </tr>
    <tr>
        <td colspan="5" style="font-weight:bold; font-size: 13px;">COD Amount PKR {{$data['courier_data']['codAmount']}}</td>
        <td colspan="3" style="font-size:14px;">Decld.Ins.Value</td>
        <td colspan="3">Rs, 0/-</td>
        {{--<td>84</td>--}}
        {{--<td>85</td>--}}
        {{--<td>86</td>--}}
        {{--<td>87</td>--}}
        {{--<td>88</td>--}}
        {{--<td>89</td>--}}
        {{--<td>90</td>--}}
        {{--<td>91</td>--}}
        {{--<td>92</td>--}}
        {{--<td>93</td>--}}
        {{--<td>94</td>--}}
        {{--<td>95</td>--}}
        {{--<td>96</td>--}}
        {{--<td>97</td>--}}
        {{--<td>98</td>--}}
        {{--<td>99</td>--}}
        {{--<td>100</td>--}}

    </tr>
    <tr>
        <td colspan="10" rowspan="2" style="padding: 4px 3px;">Shipper:{{$data['courier_data']['ShipperName']}}-KARACHI<br>
            {{$data['courier_data']['ShipperAddress']}}</td>
        <td colspan="11" rowspan="2" style="padding: 9px 3px; "><p style="margin-top: -10px;">Consignee: {{$data['courier_data']['ConsigneeName']}} {{$data['courier_data']['ContactNo']}}<br>
                {{$data['courier_data']['ConsigneeAddress']}}</p></td>
        {{--<td>103</td>--}}
        {{--<td>104</td>--}}
        {{--<td>105</td>--}}
        {{--<td>106</td>--}}
        {{--<td>107</td>--}}
        {{--<td>108</td>--}}
        {{--<td>109</td>--}}
        {{--<td>200</td>--}}
        {{--<td>201</td>--}}
        {{--<td>202</td>--}}
        {{--<td>203</td>--}}
        {{--<td>204</td>--}}
        {{--<td>205</td>--}}
        {{--<td>206</td>--}}
        {{--<td>207</td>--}}
        {{--<td>208</td>--}}
        {{--<td>209</td>--}}
        {{--<td>300</td>--}}

    </tr>
    <tr>
        {{--<td>301</td>--}}
        {{--<td>302</td>--}}
        {{--<td>303</td>--}}
        {{--<td>304</td>--}}
        {{--<td>305</td>--}}
        {{--<td>306</td>--}}
        {{--<td>307</td>--}}
        {{--<td>308</td>--}}
        {{--<td>309</td>--}}
        {{--<td>400</td>--}}
        {{--<td>401</td>--}}
        {{--<td>402</td>--}}
        {{--<td>403</td>--}}
        {{--<td>404</td>--}}
        {{--<td>405</td>--}}
        {{--<td>406</td>--}}
        {{--<td>407</td>--}}
        {{--<td>408</td>--}}
        {{--<td>409</td>--}}
        {{--<td>410</td>--}}

    </tr>
    <tr>
        <td colspan="5">Customer Ref. #</td>
        <td style="border-right:1px solid white;">None</td>
        <td style="border-right:1px solid white;border-left:1px solid white;">&nbsp</td>
        <td style="border-right:1px solid white;border-left:1px solid white;">&nbsp</td>
        <td style="border-right:1px solid white;border-left:1px solid white;">&nbsp</td>
        <td style="border-right:1px solid white;border-left:1px solid white;">&nbsp</td>
        <td colspan="3" style="font-weight: bold;">Remarks</td>
        <td colspan="8">None</td>
        {{--<td>419</td>--}}
        {{--<td>420</td>--}}
        {{--<td>421</td>--}}
        {{--<td>422</td>--}}
        {{--<td>423</td>--}}
        {{--<td>424</td>--}}
        {{--<td>425</td>--}}
        {{--<td>426</td>--}}
        {{--<td>427</td>--}}


    </tr>




</table>
<div class="container-fluid" style="border:2px solid grey;  height:170px; width:826px;"><p style="font-family:Arial; margin-top:-1px; padding:10px;">
        Product Details: </p>

</div>


<p style="margin-left:50px;">- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - </p>


<table>


    <tr>
        <td colspan="10" rowspan="5"><img src="{{URL::asset('call_courier.png')}}" style="margin-top:-5px;" alt=""/><h4 style="float:right; border: 1px solid grey; padding:5px 20px;">Shipper's Copy</h4><br><img src="http://barcodes4.me/barcode/c39/{{$data['courier_data']['ConsignmentNo']}}.png?width=355&height=55" style="margin-left:50px;margin-top:-6px;" ><br><p style="margin-top:-5px; margin-left: 150px;">{{$data['courier_data']['ConsignmentNo']}}</p></td>
        <td rowspan="8" style="border-top:1px solid white;"></td>
        <td colspan="2">Date</td>
        <td colspan="3">{{$data['date']}}</td>
        <td colspan="3">Time</td>
        <td colspan="3">{{$data['time']}}</td>
        {{--<td>7</td>--}}
        {{--<td>8</td>--}}
        {{--<td>9</td>--}}
        {{--<td>10</td>--}}
        {{--<td>11</td>--}}
        {{--<td>12</td>--}}
        {{--<td>13</td>--}}
        {{--<td>14</td>--}}
        {{--<td>15</td>--}}
        {{--<td>16</td>--}}
        {{--<td>17</td>--}}
        {{--<td>18</td>--}}
        {{--<td>19</td>--}}
        {{--<td>20</td>--}}

    </tr>
    <tr>
        <td colspan="2">Service</td>
        <td colspan="3">{{$data['service']}}</td>
        <td colspan="3">Weight</td>
        <td colspan="3">{{$data['order_data']['weight']}}</td>
        {{--<td>25</td>--}}
        {{--<td>26</td>--}}
        {{--<td>27</td>--}}
        {{--<td>28</td>--}}
        {{--<td>29</td>--}}
        {{--<td>30</td>--}}
        {{--<td>31</td>--}}
        {{--<td>32</td>--}}
        {{--<td>33</td>--}}
        {{--<td>34</td>--}}
        {{--<td>35</td>--}}
        {{--<td>36</td>--}}
        {{--<td>37</td>--}}
        {{--<td>38</td>--}}
        {{--<td>39</td>--}}
        {{--<td>40</td>--}}

    </tr>
    <tr>
        <td colspan="2">Fragile</td>
        <td colspan="3">No</td>
        <td colspan="3">Pieces</td>
        <td colspan="3">{{$data['pieces']}}</td>
        {{--<td>45</td>--}}
        {{--<td>46</td>--}}
        {{--<td>47</td>--}}
        {{--<td>48</td>--}}
        {{--<td>49</td>--}}
        {{--<td>50</td>--}}
        {{--<td>51</td>--}}
        {{--<td>52</td>--}}
        {{--<td>53</td>--}}
        {{--<td>54</td>--}}
        {{--<td>55</td>--}}
        {{--<td>56</td>--}}
        {{--<td>57</td>--}}
        {{--<td>58</td>--}}
        {{--<td>59</td>--}}
        {{--<td>60</td>--}}

    </tr>
    <tr>
        <td colspan="2">Origin</td>
        <td colspan="3">{{$data['courier_data']['HomeBranch']}}</td>
        <td colspan="3">Destination</td>
        <td colspan="3">{{$data['courier_data']['DestBranch']}}</td>
        {{--<td>65</td>--}}
        {{--<td>66</td>--}}
        {{--<td>67</td>--}}
        {{--<td>68</td>--}}
        {{--<td>69</td>--}}
        {{--<td>70</td>--}}
        {{--<td>71</td>--}}
        {{--<td>72</td>--}}
        {{--<td>73</td>--}}
        {{--<td>74</td>--}}
        {{--<td>75</td>--}}
        {{--<td>76</td>--}}
        {{--<td>77</td>--}}
        {{--<td>78</td>--}}
        {{--<td>79</td>--}}
        {{--<td>80</td>--}}

    </tr>
    <tr>
        <td colspan="5" style="font-weight:bold; font-size: 13px;">COD Amount PKR {{$data['courier_data']['codAmount']}}</td>
        <td colspan="3" style="font-size:14px;">Decld.Ins.Value</td>
        <td colspan="3">Rs, 0/-</td>
        {{--<td>84</td>--}}
        {{--<td>85</td>--}}
        {{--<td>86</td>--}}
        {{--<td>87</td>--}}
        {{--<td>88</td>--}}
        {{--<td>89</td>--}}
        {{--<td>90</td>--}}
        {{--<td>91</td>--}}
        {{--<td>92</td>--}}
        {{--<td>93</td>--}}
        {{--<td>94</td>--}}
        {{--<td>95</td>--}}
        {{--<td>96</td>--}}
        {{--<td>97</td>--}}
        {{--<td>98</td>--}}
        {{--<td>99</td>--}}
        {{--<td>100</td>--}}

    </tr>
    <tr>
        <td colspan="10" rowspan="2" style="padding: 4px 3px;">Shipper:{{$data['courier_data']['ShipperName']}}-KARACHI<br>
            {{$data['courier_data']['ShipperAddress']}}</td>
        <td colspan="11" rowspan="2" style="padding: 9px 3px; "><p style="margin-top: -10px;">Consignee: {{$data['courier_data']['ConsigneeName']}} {{$data['courier_data']['ContactNo']}}<br>
                {{$data['courier_data']['ConsigneeAddress']}}</p></td>
        {{--<td>103</td>--}}
        {{--<td>104</td>--}}
        {{--<td>105</td>--}}
        {{--<td>106</td>--}}
        {{--<td>107</td>--}}
        {{--<td>108</td>--}}
        {{--<td>109</td>--}}
        {{--<td>200</td>--}}
        {{--<td>201</td>--}}
        {{--<td>202</td>--}}
        {{--<td>203</td>--}}
        {{--<td>204</td>--}}
        {{--<td>205</td>--}}
        {{--<td>206</td>--}}
        {{--<td>207</td>--}}
        {{--<td>208</td>--}}
        {{--<td>209</td>--}}
        {{--<td>300</td>--}}

    </tr>
    <tr>
        {{--<td>301</td>--}}
        {{--<td>302</td>--}}
        {{--<td>303</td>--}}
        {{--<td>304</td>--}}
        {{--<td>305</td>--}}
        {{--<td>306</td>--}}
        {{--<td>307</td>--}}
        {{--<td>308</td>--}}
        {{--<td>309</td>--}}
        {{--<td>400</td>--}}
        {{--<td>401</td>--}}
        {{--<td>402</td>--}}
        {{--<td>403</td>--}}
        {{--<td>404</td>--}}
        {{--<td>405</td>--}}
        {{--<td>406</td>--}}
        {{--<td>407</td>--}}
        {{--<td>408</td>--}}
        {{--<td>409</td>--}}
        {{--<td>410</td>--}}

    </tr>
    <tr>
        <td colspan="5">Customer Ref. #</td>
        <td style="border-right:1px solid white;">None</td>
        <td style="border-right:1px solid white;border-left:1px solid white;">&nbsp</td>
        <td style="border-right:1px solid white;border-left:1px solid white;">&nbsp</td>
        <td style="border-right:1px solid white;border-left:1px solid white;">&nbsp</td>
        <td style="border-right:1px solid white;border-left:1px solid white;">&nbsp</td>
        <td colspan="3" style="font-weight: bold;">Remarks</td>
        <td colspan="8">None</td>
        {{--<td>419</td>--}}
        {{--<td>420</td>--}}
        {{--<td>421</td>--}}
        {{--<td>422</td>--}}
        {{--<td>423</td>--}}
        {{--<td>424</td>--}}
        {{--<td>425</td>--}}
        {{--<td>426</td>--}}
        {{--<td>427</td>--}}


    </tr>




</table>
<div class="container-fluid" style="border:2px solid grey;  width:826px;height:170px;"><p style="font-family:Arial; margin-top:-1px; padding:10px;">
        Product Details: </p>

</div>


</body>
</html>
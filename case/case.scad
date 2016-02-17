$fn=200;
epsilon=0.2;
top=false;
//lower
difference() {

//All
difference() {
translate([-2,-2,-1]) cube(size=[44,66,60]);

union() {
//Battery
difference() {
	cube(size=[40,62,21.1]);
	translate ([-0.1 + (40-5)/2, -0.1, -0.5]) cube(size=[5.1,3.1,22]);
}

//Powerplug
translate([40/2,63,20]) rotate([90,0,0]) cylinder(h=10,r=10/2,center=true);

//wire
translate([0,0,21]) cube(size=[40,62,21.1]);

//µC
translate([0,0,41]) difference() {
cube(size=[40,62,17]);
translate([40/2-15/2,-0.1,9]) cube(size=[15,30,1.5]);
}

//Switch
translate([40/2,45,60]) cylinder(h=10,r=9/2,center=true);


//LED
translate([40/2,55,60]) cylinder(h=10,r=5/2,center=true);
}
}

if(top) {
	translate([-30,-30,-52]) cube(size=[100,100,100]);
	translate([-1-epsilon,-1-epsilon,-50.01]) cube(size=[42+2*epsilon,64+2*epsilon,100]);
} else {
	difference() {
		translate([-60,-60,-60]) cube(size=[1000,1000,1000]);
		translate([-30,-30,-52]) cube(size=[100,100,100]);
		translate([-1+epsilon,-1+epsilon,-50.01]) cube(size=[42-2*epsilon,64-2*epsilon,100]);
	}
}
}
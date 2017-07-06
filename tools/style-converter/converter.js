
const readline = require('readline');
const fs = require('fs');
const rl = readline.createInterface({
  input: process.stdin,
  output: process.stdout
});

instal = function() {
  console.log("Looking for styles in the ./styles/ directory");
  if(fs.existsSync("./styles/")){

  }
  else console.log("Can't find any styles.");
  rl.close();
};


instal();

import { Dimensions, Platform } from 'react-native';
import { scale } from './HelperFunctions/functions';

const { width, height } = Dimensions.get('window');

let screenHeight = width < height ? height : width
let screenWidth = width < height ? width : height

const cv = (val) => {
  return scale(val)
};

export default cv;

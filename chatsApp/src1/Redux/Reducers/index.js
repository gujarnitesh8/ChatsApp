import AuthReducer from './AuthReducer';
import CommonReducer from './CommonReducer';
import HomeReducer from './HomeReducer';

let rootReducer = {
    Auth: AuthReducer,
    Home: HomeReducer,
    Common: CommonReducer
};
export default rootReducer
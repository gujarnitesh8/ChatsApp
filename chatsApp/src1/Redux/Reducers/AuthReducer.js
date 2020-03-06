import {
    GET_USER_LOGIN_SUCCESS,
    GET_USER_LOGIN_FAILED,
    GET_USER_REGISTER_SUCCESS,
    GET_USER_REGISTER_FAILED,
} from '../Types';


const INITIAL_STATE = {}

export default (state = INITIAL_STATE, action) => {
    switch (action.type) {

        case GET_USER_LOGIN_SUCCESS:
            return { ...state, getUserLoginSuccess: true, loginUser: action.payload }

        case GET_USER_LOGIN_FAILED:
            return { ...state, getUserLoginSuccess: false, loginUserFail: action.payload }

        case GET_USER_REGISTER_SUCCESS:
            return { ...state, getUserRegistrationSuccess: true, registerUser: action.payload }

        case GET_USER_REGISTER_FAILED:
            return { ...state, getUserRegistrationSuccess: false, registerUserFail: action.payload }

        default:
            return state;
    }
}